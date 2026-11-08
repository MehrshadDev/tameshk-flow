<?php

namespace App\Http\Controllers;

use App\Terminal\TerminalExecutor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

class TerminalController extends Controller
{
    public function __construct(
        private readonly TerminalExecutor $executor,
    ) {}

    public function index(): View
    {
        $catalog = $this->executor->autocompleteCatalog();
        $logFile = storage_path('logs/laravel.log');
        $logSize = file_exists($logFile) ? (@filesize($logFile) ?: 0) : 0;

        return view('pages.terminal.index', [
            'quickGroups' => config('terminal.quick_groups', []),
            'catalog' => $catalog,
            'systemInfo' => [
                'php' => PHP_VERSION,
                'laravel' => app()->version(),
                'env' => app()->environment(),
                'debug' => config('app.debug'),
                'user' => get_current_user() ?: 'www-data',
                'host' => parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'localhost',
                'log_size_kb' => round($logSize / 1024, 1),
            ],
        ]);
    }

    public function execute(Request $request): JsonResponse
    {
        $rateKey = 'web-terminal:' . $request->user()->id;

        if (RateLimiter::tooManyAttempts($rateKey, 45)) {
            return response()->json([
                'status' => 'ok',
                'output' => '[RATE LIMIT] Too many commands executed. Please wait a few seconds.',
                'cwd' => (string) $request->input('cwd', ''),
                'exit_code' => 429,
                'duration_ms' => 0,
            ], 429);
        }

        RateLimiter::hit($rateKey, 60);

        $data = $request->validate([
            'command' => ['required', 'string', 'max:1000'],
            'cwd' => ['nullable', 'string', 'max:255'],
            'confirmed' => ['nullable', 'boolean'],
        ]);

        $result = $this->executor->execute(
            rawCommand: $data['command'],
            relativeCwd: (string) ($data['cwd'] ?? ''),
            confirmed: (bool) ($data['confirmed'] ?? false),
            userEmail: $request->user()->email,
        );

        $catalog = $this->executor->autocompleteCatalog($result['cwd'] ?? '');
        $result['files'] = $catalog['files'];

        return response()->json($result);
    }
}
