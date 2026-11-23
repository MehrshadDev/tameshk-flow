<?php

namespace App\Http\Controllers;

use App\Jobs\RunWorkflowJob;
use App\Models\Workflow;
use App\Subscriptions\SubscriptionManager;
use App\Workflow\Nodes\Triggers\FormTriggerNode;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FormWebhookController extends Controller
{
    public function __construct(
        private readonly SubscriptionManager $subscriptions,
    ) {}

    public function show(Request $request, string $token): View
    {
        [, $trigger] = $this->resolve($request, $token);

        $config = is_array($trigger->config) ? $trigger->config : [];
        $fields = FormTriggerNode::normalizedFields($config);

        return view('pages.form.public', [
            'title'           => $this->text($config, 'title', 'فرم تماس با ما'),
            'description'     => trim((string) ($config['description'] ?? '')),
            'submitLabel'     => $this->text($config, 'submit_label', 'ارسال پیام'),
            'responseMessage' => $this->text($config, 'response_message', 'پیام شما با موفقیت ثبت شد. از همراهی شما سپاسگزاریم!'),
            'fields'          => $fields,
            'endpoint'        => route('webhooks.form.submit', ['token' => $token], false),
        ]);
    }

    public function submit(Request $request, string $token): JsonResponse
    {
        [$workflow, $trigger] = $this->resolve($request, $token);

        $workflow->loadMissing('user');

        if (! $workflow->user || ! $this->subscriptions->hasRunQuota($workflow->user)) {
            return response()->json([
                'message' => 'ظرفیت پذیرش فرم در حال حاضر تکمیل شده است.',
            ], 429);
        }

        $config = is_array($trigger->config) ? $trigger->config : [];
        $fields = FormTriggerNode::normalizedFields($config);
        $inputValues = is_array($request->input('fields')) ? $request->input('fields') : $request->all();

        $errors = [];
        $cleanValues = [];

        foreach ($fields as $field) {
            $name = $field['name'];
            $label = $field['label'];
            $type = $field['type'];
            $required = $field['required'];

            $rawVal = $inputValues[$name] ?? '';
            $val = is_scalar($rawVal) ? trim(strip_tags((string) $rawVal)) : '';

            if ($required && $val === '') {
                $errors[$name] = "وارد کردن «{$label}» الزامی است.";
                continue;
            }

            if ($val === '') {
                $cleanValues[$name] = '';
                continue;
            }

            if (mb_strlen($val) > 5000) {
                $errors[$name] = "مقدار «{$label}» بیش از حد طولانی است.";
                continue;
            }

            if ($type === 'email' && ! filter_var($val, FILTER_VALIDATE_EMAIL)) {
                $errors[$name] = "فرمت ایمیل واردشده در «{$label}» معتبر نیست.";
                continue;
            }

            if ($type === 'number') {
                $normalizedNum = strtr($val, [
                    '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
                    '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
                    '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
                    '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
                ]);

                if (! is_numeric($normalizedNum)) {
                    $errors[$name] = "مقدار «{$label}» باید یک عدد معتبر باشد.";
                    continue;
                }

                $cleanValues[$name] = str_contains($normalizedNum, '.') ? (float) $normalizedNum : (int) $normalizedNum;
                continue;
            }

            if ($type === 'select' && $field['options_list'] !== [] && ! in_array($val, $field['options_list'], true)) {
                $errors[$name] = "گزینه انتخاب‌شده برای «{$label}» معتبر نیست.";
                continue;
            }

            $cleanValues[$name] = $val;
        }

        if ($errors !== []) {
            return response()->json([
                'message' => reset($errors),
                'errors'  => $errors,
            ], 422);
        }

        $payload = FormTriggerNode::buildSubmissionPayload($cleanValues, $request->ip());
        $responseMessage = $this->text($config, 'response_message', 'پیام شما با موفقیت ثبت شد. از همراهی شما سپاسگزاریم!');

        RunWorkflowJob::dispatchSmart($workflow->id, $payload);

        return response()->json([
            'ok'      => true,
            'message' => $responseMessage,
        ]);
    }

    private function resolve(Request $request, string $token): array
    {
        $workflow = Workflow::query()->where('webhook_token', $token)->first();

        if (! $workflow || ! $workflow->isPublished() || ! $workflow->is_active) {
            throw $this->notRegistered($request, $token);
        }

        $workflow->load('nodes');

        $trigger = $workflow->nodes->firstWhere('type', 'trigger.form');

        if (! $trigger) {
            throw $this->notRegistered($request, $token);
        }

        return [$workflow, $trigger];
    }

    private function notRegistered(Request $request, string $token): HttpResponseException
    {
        return new HttpResponseException(response()->json([
            'code'    => 404,
            'message' => "The requested webhook \"{$request->method()} $token/form\" is not registered.",
            'hint'    => 'The workflow must be published and active for the production URL to work.',
        ], 404));
    }

    private function text(array $config, string $key, string $default): string
    {
        $value = trim((string) ($config[$key] ?? ''));

        return $value !== '' ? $value : $default;
    }
}
