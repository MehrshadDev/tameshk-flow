<?php

namespace App\Credentials;

use App\Models\Credential;
use App\Workflow\Support\SafeHttpClient;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

final class AiModelCatalogService
{
    private const CACHE_TTL_HOURS = 2;

    public function listForCredential(Credential $credential, bool $forceRefresh = false): array
    {
        $apiKey = trim((string) $credential->get('api_key'));

        if ($apiKey === '') {
            return [];
        }

        $baseUrl = $credential->type === 'openai'
            ? trim((string) $credential->get('base_url', ''))
            : '';

        $cacheKey = 'ai_live_models_v1_' . $credential->type . '_' . hash('sha256', $apiKey . '|' . $baseUrl);

        if (! $forceRefresh) {
            $cached = Cache::get($cacheKey);

            if (is_array($cached) && $cached !== []) {
                return $cached;
            }
        }

        $models = match ($credential->type) {
            'gemini'     => $this->fetchGeminiModels($apiKey),
            'openai'     => $this->fetchOpenAiModels($apiKey, $baseUrl),
            'anthropic'  => $this->fetchAnthropicModels($apiKey),
            'deepseek'   => $this->fetchDeepSeekModels($apiKey),
            'groq'       => $this->fetchGroqModels($apiKey),
            'openrouter' => $this->fetchOpenRouterModels($apiKey),
            default      => [],
        };

        if ($models !== []) {
            Cache::put($cacheKey, $models, now()->addHours(self::CACHE_TTL_HOURS));
        }

        return $models;
    }

    private function fetchGeminiModels(string $apiKey): array
    {
        try {
            $baseUrl = rtrim((string) config('services.gemini.base_url', 'https://generativelanguage.googleapis.com'), '/');
            $endpoint = str_ends_with($baseUrl, '/v1beta')
                ? "$baseUrl/models"
                : "$baseUrl/v1beta/models";

            $rawModels = [];
            $pageToken = null;

            for ($page = 0; $page < 5; $page++) {
                $query = ['pageSize' => 1000];

                if ($pageToken !== null) {
                    $query['pageToken'] = $pageToken;
                }

                $response = Http::withHeaders(['x-goog-api-key' => $apiKey])
                    ->acceptJson()
                    ->timeout(15)
                    ->get($endpoint, $query);

                if ($response->failed()) {
                    return [];
                }

                $json = (array) $response->json();
                $batch = (array) ($json['models'] ?? []);
                $rawModels = array_merge($rawModels, $batch);

                $nextToken = trim((string) ($json['nextPageToken'] ?? ''));

                if ($nextToken === '') {
                    break;
                }

                $pageToken = $nextToken;
            }

            return collect($rawModels)
                ->filter(fn ($m) => is_array($m) && $this->isGeminiTextModel($m))
                ->sortBy(fn (array $m) => str_contains((string) ($m['name'] ?? ''), 'preview') ? 1 : 0)
                ->unique(fn (array $m) => $this->shortGeminiLabel((string) ($m['displayName'] ?? $m['name'] ?? '')))
                ->map(function (array $m) {
                    $id = str_replace('models/', '', (string) ($m['name'] ?? ''));

                    return [
                        'value'       => $id,
                        'label'       => $this->shortGeminiLabel((string) ($m['displayName'] ?? $id)),
                        'description' => trim((string) ($m['description'] ?? '')),
                    ];
                })
                ->filter(fn (array $m) => $m['value'] !== '')
                ->unique('value')
                ->sortByDesc('value')
                ->values()
                ->all();
        } catch (Throwable $e) {
            report($e);

            return [];
        }
    }

    private function fetchOpenAiModels(string $apiKey, string $customBaseUrl): array
    {
        try {
            $baseUrl = rtrim($customBaseUrl !== '' ? $customBaseUrl : 'https://api.openai.com/v1', '/');
            $isOfficial = str_contains(strtolower($baseUrl), 'api.openai.com');

            if (! $isOfficial) {
                (new SafeHttpClient())->assertSafeUrl($baseUrl);
            }

            $response = Http::withToken($apiKey)
                ->acceptJson()
                ->timeout(15)
                ->get("$baseUrl/models");

            if ($response->failed()) {
                return [];
            }

            $rawModels = (array) data_get($response->json(), 'data', []);

            return collect($rawModels)
                ->filter(fn ($m) => is_array($m) && $this->isOpenAiChatModel($m, $isOfficial))
                ->sortByDesc(fn (array $m) => (int) ($m['created'] ?? 0))
                ->map(function (array $m) {
                    $id = trim((string) ($m['id'] ?? ''));
                    $label = trim((string) ($m['name'] ?? $m['display_name'] ?? ''));
                    $ownedBy = trim((string) ($m['owned_by'] ?? ''));
                    $desc = trim((string) ($m['description'] ?? ''));

                    return [
                        'value'       => $id,
                        'label'       => $label !== '' ? $label : $id,
                        'description' => $desc !== '' ? mb_substr($desc, 0, 180) : $ownedBy,
                    ];
                })
                ->filter(fn (array $m) => $m['value'] !== '')
                ->unique('value')
                ->values()
                ->all();
        } catch (Throwable $e) {
            report($e);

            return [];
        }
    }

    private function fetchAnthropicModels(string $apiKey): array
    {
        try {
            $rawModels = [];
            $afterId = null;

            for ($page = 0; $page < 5; $page++) {
                $query = ['limit' => 100];

                if ($afterId !== null) {
                    $query['after_id'] = $afterId;
                }

                $response = Http::withHeaders([
                    'x-api-key'         => $apiKey,
                    'anthropic-version' => '2023-06-01',
                ])
                    ->acceptJson()
                    ->timeout(15)
                    ->get('https://api.anthropic.com/v1/models', $query);

                if ($response->failed()) {
                    return [];
                }

                $json = (array) $response->json();
                $batch = (array) ($json['data'] ?? []);
                $rawModels = array_merge($rawModels, $batch);

                if (empty($json['has_more']) || empty($json['last_id'])) {
                    break;
                }

                $afterId = (string) $json['last_id'];
            }

            return collect($rawModels)
                ->filter(fn ($m) => is_array($m) && trim((string) ($m['id'] ?? '')) !== '')
                ->map(function (array $m) {
                    $id = trim((string) $m['id']);
                    $displayName = trim((string) ($m['display_name'] ?? ''));

                    return [
                        'value'       => $id,
                        'label'       => $displayName !== '' ? $displayName : $id,
                        'description' => trim((string) ($m['description'] ?? '')),
                    ];
                })
                ->unique('value')
                ->values()
                ->all();
        } catch (Throwable $e) {
            report($e);

            return [];
        }
    }

    private function fetchDeepSeekModels(string $apiKey): array
    {
        try {
            $response = Http::withToken($apiKey)
                ->acceptJson()
                ->timeout(15)
                ->get('https://api.deepseek.com/v1/models');

            if ($response->failed()) {
                return [];
            }

            $rawModels = (array) data_get($response->json(), 'data', []);

            return collect($rawModels)
                ->filter(function ($m) {
                    if (! is_array($m) || trim((string) ($m['id'] ?? '')) === '') {
                        return false;
                    }

                    if (isset($m['output_modalities']) && is_array($m['output_modalities'])) {
                        return in_array('text', $m['output_modalities'], true);
                    }

                    return true;
                })
                ->map(function (array $m) {
                    $id = trim((string) $m['id']);
                    $name = trim((string) ($m['name'] ?? ''));
                    $contextWindow = (int) ($m['context_window'] ?? 0);
                    $desc = $contextWindow > 0 ? $this->formatContextLength($contextWindow) : '';

                    return [
                        'value'       => $id,
                        'label'       => $name !== '' ? $name : $id,
                        'description' => $desc,
                    ];
                })
                ->unique('value')
                ->values()
                ->all();
        } catch (Throwable $e) {
            report($e);

            return [];
        }
    }

    private function fetchGroqModels(string $apiKey): array
    {
        try {
            $response = Http::withToken($apiKey)
                ->acceptJson()
                ->timeout(15)
                ->get('https://api.groq.com/openai/v1/models');

            if ($response->failed()) {
                return [];
            }

            $rawModels = (array) data_get($response->json(), 'data', []);

            return collect($rawModels)
                ->filter(fn ($m) => is_array($m) && $this->isGroqChatModel($m))
                ->sortByDesc(fn (array $m) => (int) ($m['created'] ?? 0))
                ->map(function (array $m) {
                    $id = trim((string) ($m['id'] ?? ''));
                    $ownedBy = trim((string) ($m['owned_by'] ?? ''));
                    $contextWindow = (int) ($m['context_window'] ?? 0);

                    $metaParts = array_filter([
                        $ownedBy,
                        $contextWindow > 0 ? $this->formatContextLength($contextWindow) : '',
                    ]);

                    return [
                        'value'       => $id,
                        'label'       => $id,
                        'description' => implode(' · ', $metaParts),
                    ];
                })
                ->filter(fn (array $m) => $m['value'] !== '')
                ->unique('value')
                ->values()
                ->all();
        } catch (Throwable $e) {
            report($e);

            return [];
        }
    }

    private function fetchOpenRouterModels(string $apiKey): array
    {
        try {
            $response = Http::withToken($apiKey)
                ->acceptJson()
                ->timeout(20)
                ->get('https://openrouter.ai/api/v1/models');

            if ($response->failed()) {
                return [];
            }

            $rawModels = (array) data_get($response->json(), 'data', []);

            return collect($rawModels)
                ->filter(function ($m) {
                    if (! is_array($m) || trim((string) ($m['id'] ?? '')) === '') {
                        return false;
                    }

                    $outputModalities = data_get($m, 'architecture.output_modalities');

                    if (is_array($outputModalities) && ! in_array('text', $outputModalities, true)) {
                        return false;
                    }

                    return true;
                })
                ->sortByDesc(fn (array $m) => (int) ($m['created'] ?? 0))
                ->map(function (array $m) {
                    $id = trim((string) $m['id']);
                    $name = trim((string) ($m['name'] ?? ''));
                    $description = trim(preg_replace('/\s+/u', ' ', (string) ($m['description'] ?? '')) ?? '');

                    return [
                        'value'       => $id,
                        'label'       => $name !== '' ? $name : $id,
                        'description' => mb_substr($description, 0, 180),
                    ];
                })
                ->unique('value')
                ->values()
                ->all();
        } catch (Throwable $e) {
            report($e);

            return [];
        }
    }

    private function isGeminiTextModel(array $model): bool
    {
        if (! in_array('generateContent', $model['supportedGenerationMethods'] ?? [], true)) {
            return false;
        }

        $id = str_replace('models/', '', (string) ($model['name'] ?? ''));

        if (! str_starts_with($id, 'gemini-')) {
            return false;
        }

        if (preg_match('/^gemini-(1\.|2\.0)/i', $id)) {
            return false;
        }

        return ! preg_match(
            '/image|nano-banana|tts|transcribe|lyria|embed|aqa|deep-research|antigravity|robotics|computer-use|omni|customtools|live|audio/i',
            $id
        );
    }

    private function isOpenAiChatModel(array $model, bool $isOfficial): bool
    {
        $id = trim((string) ($model['id'] ?? ''));

        if ($id === '') {
            return false;
        }

        if (preg_match(
            '/embed|whisper|tts|dall-e|moderation|transcribe|realtime|audio|sora|imagen|lyria|rerank|babbage|davinci|curie|ada|gpt-image|chatgpt-image|image-generation|computer-use|-instruct/i',
            $id
        )) {
            return false;
        }

        if (! $isOfficial) {
            return true;
        }

        return (bool) preg_match('/^(gpt-|o\d|chatgpt-)/i', $id);
    }

    private function isGroqChatModel(array $model): bool
    {
        if (array_key_exists('active', $model) && $model['active'] === false) {
            return false;
        }

        $id = trim((string) ($model['id'] ?? ''));

        if ($id === '') {
            return false;
        }

        return ! preg_match(
            '/whisper|playai|orpheus|tts|stt|transcribe|guard|safeguard|embed|rerank/i',
            $id
        );
    }

    private function shortGeminiLabel(string $name): string
    {
        $name = preg_replace('/\s*\(.*?\)\s*/', ' ', $name) ?? $name;
        $name = preg_replace('/\s+(\d{2}-\d{4}|\d{2}-\d{2}|\d{3})$/', '', trim($name)) ?? $name;
        $name = preg_replace('/\s+Preview$/i', '', trim($name)) ?? $name;

        return trim($name);
    }

    private function formatContextLength(int $tokens): string
    {
        if ($tokens >= 1000000) {
            return round($tokens / 1000000, 1) . 'M context';
        }

        if ($tokens >= 1000) {
            return (int) round($tokens / 1000) . 'K context';
        }

        return $tokens . ' context';
    }
}
