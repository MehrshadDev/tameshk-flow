<?php

namespace App\Http\Controllers;

use App\Templates\PendingTemplate;
use App\Templates\TemplateInstaller;
use App\Templates\TemplateRepository;
use App\Workflow\Registry\NodeRegistry;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

class TemplateController extends Controller
{
    public function index(): View
    {
        return view('pages.templates.index', [
            'templates' => TemplateRepository::all(),
            'categories' => TemplateRepository::categoriesWithCounts(),
            'apps' => TemplateRepository::appsWithCounts(),
        ]);
    }

    public function show(string $slug, NodeRegistry $nodeRegistry): View
    {
        $template = TemplateRepository::find($slug);

        abort_if($template === null, 404);

        return view('pages.templates.show', [
            'template' => $template,
            'relatedDocs' => TemplateRepository::relatedDocsFor($template),
            'relatedTemplates' => TemplateRepository::relatedTemplatesFor($template, 3),
            'definitions' => $nodeRegistry->definitions(),
        ]);
    }

    public function use(
        Request $request,
        string $slug,
        TemplateInstaller $installer,
        PendingTemplate $pending,
    ): RedirectResponse {
        $template = TemplateRepository::find($slug);

        abort_if($template === null, 404);

        $user = $request->user();

        if (! $user) {
            $pending->remember($template->slug);

            return redirect()->route('register');
        }

        $lockKey = "template-install-lock:{$user->id}";

        if (! Cache::add($lockKey, true, 3)) {
            return redirect()
                ->route('templates.show', $template->slug)
                ->with('template_error', 'درخواست قبلی شما در حال پردازش است. لطفاً چند لحظه صبر کنید.');
        }

        $rateKey = 'workflow-create:' . $user->id;

        if (RateLimiter::tooManyAttempts($rateKey, (int) config('workflow.create_per_minute', 10))) {
            Cache::forget($lockKey);

            return redirect()
                ->route('templates.show', $template->slug)
                ->with('template_error', 'تعداد درخواست‌های ساخت ورکفلو بیش از حد مجاز است. لطفاً یک دقیقه دیگر تلاش کنید.');
        }

        RateLimiter::hit($rateKey);

        try {
            $workflow = $installer->install($user, $template);
        } catch (AuthorizationException $e) {
            Cache::forget($lockKey);

            return redirect()
                ->route('templates.show', $template->slug)
                ->with('template_error', $e->getMessage());
        }

        return redirect()->route('app.workflows.show', $workflow);
    }
}
