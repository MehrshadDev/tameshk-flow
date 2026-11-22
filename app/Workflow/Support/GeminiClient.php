<?php

namespace App\Workflow\Support;

use App\Models\Credential;
use App\Workflow\Exceptions\NodeExecutionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Throwable;

final class GeminiClient
{
    private string $apiKey;
    private string $baseUrl;

    public function __construct(private readonly Credential $credential)
    {
        if ($this->credential->type !== 'gemini') {
            throw NodeExecutionException::because('نوع اعتبارنامه انتخابی برای Google Gemini معتبر نیست.');
        }

        $this->apiKey = trim((string) $this->credential->get('api_key'));
        if ($this->apiKey === '') {
            throw NodeExecutionException::because('کلید API در اعتبارنامه Gemini خالی است.');
        }

        $this->baseUrl = rtrim((string) config('services.gemini.base_url', 'https://generativelanguage.googleapis.com'), '/');
    }

    public function generateContent(
        string $model,
        array $contents,
        array $generationConfig = [],
        ?string $systemInstruction = null,
        array $tools = []
    ): array {
        $cleanModel = $this->cleanModelName($model);
        $payload = ['contents' => $contents];

        if ($generationConfig !== []) {
            $payload['generationConfig'] = $generationConfig;
        }

        if ($systemInstruction !== null && trim($systemInstruction) !== '') {
            $payload['system_instruction'] = [
                'parts' => [['text' => trim($systemInstruction)]],
            ];
        }

        if ($tools !== []) {
            $payload['tools'] = $tools;
        }

        return $this->call('POST', "/models/$cleanModel:generateContent", $payload, 120);
    }

    public function ask(
        string $model,
        array $parts,
        array $generationConfig = [],
        ?string $systemInstruction = null,
        array $tools = []
    ): array {
        $raw = $this->generateContent(
            $model,
            [['role' => 'user', 'parts' => $parts]],
            $generationConfig,
            $systemInstruction,
            $tools,
        );

        return [
            'text' => self::text($raw),
            'grounding_metadata' => data_get($raw, 'candidates.0.groundingMetadata'),
            'raw' => $raw,
        ];
    }

    public function generateImages(string $prompt, array $options = []): array
    {
        $selectedModel = trim((string) ($options['model'] ?? 'auto'));
        $aspectRatio = (string) ($options['aspect_ratio'] ?? '1:1');
        $sampleCount = max(1, min(4, (int) ($options['number_of_images'] ?? 1)));
        $outputFormat = (string) ($options['output_format'] ?? 'image/png');

        if ($selectedModel !== 'auto' && str_starts_with($selectedModel, 'imagen-')) {
            return $this->generateWithImagen($selectedModel, $prompt, $sampleCount, $aspectRatio, $outputFormat);
        }

        if ($selectedModel !== 'auto' && str_starts_with($selectedModel, 'gemini-')) {
            return $this->generateWithGeminiNative($selectedModel, [['text' => $prompt]], $outputFormat);
        }

        $candidates = [
            ['type' => 'gemini', 'model' => 'gemini-2.5-flash-image'],
            ['type' => 'gemini', 'model' => 'gemini-3.1-flash-image-preview'],
            ['type' => 'imagen', 'model' => 'imagen-4.0-generate-001'],
            ['type' => 'imagen', 'model' => 'imagen-3.0-generate-002'],
        ];

        $lastError = null;

        foreach ($candidates as $candidate) {
            try {
                if ($candidate['type'] === 'gemini') {
                    return $this->generateWithGeminiNative($candidate['model'], [['text' => $prompt]], $outputFormat);
                }

                return $this->generateWithImagen($candidate['model'], $prompt, $sampleCount, $aspectRatio, $outputFormat);
            } catch (Throwable $e) {
                $lastError = $e;
            }
        }

        throw $lastError instanceof NodeExecutionException
            ? $lastError
            : NodeExecutionException::because('تولید تصویر با مدل‌های Gemini/Imagen ناموفق بود.');
    }

    public function editImage(string $prompt, array $imagePart, array $options = []): array
    {
        $selectedModel = trim((string) ($options['model'] ?? 'auto'));
        $outputFormat = (string) ($options['output_format'] ?? 'image/png');
        $parts = [$imagePart, ['text' => $prompt]];

        if ($selectedModel !== 'auto' && str_starts_with($selectedModel, 'gemini-')) {
            return $this->generateWithGeminiNative($selectedModel, $parts, $outputFormat);
        }

        $models = [
            'gemini-2.5-flash-image',
            'gemini-3.1-flash-image-preview',
        ];

        $lastError = null;

        foreach ($models as $model) {
            try {
                return $this->generateWithGeminiNative($model, $parts, $outputFormat);
            } catch (Throwable $e) {
                $lastError = $e;
            }
        }

        throw $lastError instanceof NodeExecutionException
            ? $lastError
            : NodeExecutionException::because('ویرایش تصویر با مدل‌های Gemini ناموفق بود.');
    }

    public function uploadMedia(string $binaryContent, string $mimeType, ?string $displayName = null): array
    {
        if ($binaryContent === '') {
            throw NodeExecutionException::because('محتوای فایل برای آپلود خالی است.');
        }

        $uploadUrl = $this->buildUploadEndpoint('/files');
        $displayName = trim((string) $displayName) !== '' ? trim((string) $displayName) : 'file_' . uniqid();
        $cleanMime = trim($mimeType) !== '' ? trim($mimeType) : $this->detectMimeFromBinary($binaryContent, 'application/octet-stream');

        $metadata = json_encode([
            'file' => [
                'display_name' => $displayName,
            ],
        ], JSON_UNESCAPED_UNICODE);

        $boundary = '-------tameshk_' . md5((string) microtime(true));

        $body = "--$boundary\r\n"
            . "Content-Type: application/json; charset=UTF-8\r\n\r\n"
            . "$metadata\r\n"
            . "--$boundary\r\n"
            . "Content-Type: $cleanMime\r\n\r\n"
            . $binaryContent . "\r\n"
            . "--$boundary--\r\n";

        try {
            $response = Http::withHeaders([
                'x-goog-api-key' => $this->apiKey,
                'X-Goog-Upload-Protocol' => 'multipart',
                'Content-Type' => "multipart/related; boundary=$boundary",
            ])->timeout(180)->withBody($body, "multipart/related; boundary=$boundary")->post($uploadUrl);
        } catch (Throwable $e) {
            throw NodeExecutionException::because('خطا در آپلود فایل به Gemini Files API: ' . $e->getMessage());
        }

        $this->checkResponse($response);

        $file = (array) data_get($response->json(), 'file', $response->json());

        return $this->waitForFileActive($file);
    }

    public function getFile(string $name): array
    {
        return $this->call('GET', '/' . $this->normalizeFileName($name));
    }

    public function deleteFile(string $name): bool
    {
        $this->call('DELETE', '/' . $this->normalizeFileName($name));

        return true;
    }

    public function createFileSearchStore(string $displayName): array
    {
        return $this->call('POST', '/fileSearchStores', [
            'displayName' => trim($displayName),
        ]);
    }

    public function listFileSearchStores(int $pageSize = 50): array
    {
        $response = $this->call('GET', '/fileSearchStores', [
            'pageSize' => max(1, min(100, $pageSize)),
        ]);

        return (array) ($response['fileSearchStores'] ?? []);
    }

    public function deleteFileSearchStore(string $storeName, bool $force = true): bool
    {
        $normalized = $this->normalizeStoreName($storeName);
        $path = "/$normalized" . ($force ? '?force=true' : '');

        $this->call('DELETE', $path);

        return true;
    }

    public function uploadToFileSearchStore(
        string $storeName,
        string $binaryContent,
        string $mimeType,
        ?string $displayName = null
    ): array {
        $normalizedStore = $this->normalizeStoreName($storeName);
        $title = trim((string) $displayName) !== '' ? trim((string) $displayName) : 'document_' . date('Ymd_His');

        $uploadedFile = $this->uploadMedia($binaryContent, $mimeType, $title);
        $fileName = (string) ($uploadedFile['name'] ?? '');

        if ($fileName === '') {
            throw NodeExecutionException::because('آپلود اولیه فایل در Gemini انجام شد اما شناسه فایل (files/...) دریافت نشد.');
        }

        return $this->importFileToSearchStore($normalizedStore, $fileName, $uploadedFile);
    }

    public function importFileToSearchStore(string $storeName, string $fileName, array $uploadedFileMeta = []): array
    {
        $normalizedStore = $this->normalizeStoreName($storeName);
        $normalizedFile = $this->normalizeFileName($fileName);

        $operation = $this->call('POST', "/$normalizedStore:importFile", [
            'fileName' => $normalizedFile,
        ], 90);

        $completedOperation = $this->waitForOperation($operation, 20);

        return [
            'store_name' => $normalizedStore,
            'file_name' => $normalizedFile,
            'file_uri' => $uploadedFileMeta['uri'] ?? null,
            'mime_type' => $uploadedFileMeta['mimeType'] ?? null,
            'operation' => $completedOperation,
            'done' => (bool) ($completedOperation['done'] ?? true),
        ];
    }

    public function queryFileSearchStore(
        string $model,
        string $storeName,
        string $query,
        ?string $systemInstruction = null,
        float $temperature = 0.3
    ): array {
        $normalizedStore = $this->normalizeStoreName($storeName);

        $tools = [
            [
                'fileSearch' => [
                    'fileSearchStoreNames' => [$normalizedStore],
                ],
            ],
        ];

        return $this->ask(
            $model,
            [['text' => $query]],
            ['temperature' => $temperature],
            $systemInstruction,
            $tools,
        );
    }

    public function createCorpus(string $displayName): array
    {
        return $this->createFileSearchStore($displayName);
    }

    public function listCorpora(int $pageSize = 50): array
    {
        return $this->listFileSearchStores($pageSize);
    }

    public function deleteCorpus(string $name, bool $force = true): bool
    {
        return $this->deleteFileSearchStore($name, $force);
    }

    public function uploadFileToCorpus(string $corpusName, string $title, string $content): array
    {
        return $this->uploadToFileSearchStore($corpusName, $content, 'text/plain', $title);
    }

    public function resolveInputPart(
        string $rawInput,
        string $defaultMime = 'application/octet-stream',
        mixed $contextInput = null
    ): array {
        $source = $this->pickSourceString($rawInput, $contextInput);

        if ($source === '') {
            throw NodeExecutionException::because('فایل یا آدرس ورودی مشخص نشده است. مطمئن شوید نود قبلی خروجی فایل (مثل data_url یا base64) تولید کرده است.');
        }

        if ($this->isGeminiFileReference($source)) {
            $uri = $this->toGeminiFileUri($source);
            $mime = $this->pickMimeType($defaultMime, $contextInput);

            return [
                'fileData' => [
                    'mimeType' => $mime,
                    'fileUri' => $uri,
                ],
            ];
        }

        $extracted = $this->extractBinaryAndMime($source, $defaultMime, $contextInput);

        if (strlen($extracted['binary']) > 18 * 1024 * 1024) {
            $uploaded = $this->uploadMedia($extracted['binary'], $extracted['mime_type'], $extracted['file_name']);

            return [
                'fileData' => [
                    'mimeType' => (string) ($uploaded['mimeType'] ?? $extracted['mime_type']),
                    'fileUri' => (string) ($uploaded['uri'] ?? ''),
                ],
            ];
        }

        return [
            'inlineData' => [
                'mimeType' => $extracted['mime_type'],
                'data' => base64_encode($extracted['binary']),
            ],
        ];
    }

    public function extractBinaryAndMime(
        string $rawInput,
        string $defaultMime = 'application/octet-stream',
        mixed $contextInput = null
    ): array {
        $source = $this->pickSourceString($rawInput, $contextInput);

        if ($source === '') {
            throw NodeExecutionException::because('منبع فایل خالی است. فایل یا آدرس معتبری از نود قبلی دریافت نشد.');
        }

        $fallbackName = is_array($contextInput)
            ? trim((string) ($contextInput['file_name'] ?? $contextInput['file_path'] ?? ''))
            : '';

        if (str_starts_with($source, 'data:')) {
            $markerPos = strpos($source, ';base64,');

            if ($markerPos === false) {
                throw NodeExecutionException::because('فرمت Data URL واردشده معتبر نیست.');
            }

            $mime = trim(substr($source, 5, $markerPos - 5));
            $base64Payload = substr($source, $markerPos + 8);
            $binary = base64_decode(str_replace(["\r", "\n", ' '], '', $base64Payload), true);

            if ($binary === false || $binary === '') {
                throw NodeExecutionException::because('رمزگشایی محتوای Base64 فایل ناموفق بود.');
            }

            $resolvedMime = ($mime !== '' && $mime !== 'application/octet-stream')
                ? $mime
                : $this->detectMimeFromBinary($binary, $this->pickMimeType($defaultMime, $contextInput));

            return [
                'binary' => $binary,
                'mime_type' => $resolvedMime,
                'file_name' => $fallbackName !== '' ? basename($fallbackName) : null,
            ];
        }

        if (str_starts_with($source, 'http://') || str_starts_with($source, 'https://')) {
            $downloaded = $this->downloadBinaryFromUrl($source);
            $urlPath = (string) parse_url($source, PHP_URL_PATH);
            $extMime = $this->detectMimeFromExtension(pathinfo($urlPath, PATHINFO_EXTENSION));

            $resolvedMime = $extMime
                ?? $downloaded['mime_type']
                ?? $this->detectMimeFromBinary($downloaded['binary'], $this->pickMimeType($defaultMime, $contextInput));

            return [
                'binary' => $downloaded['binary'],
                'mime_type' => $resolvedMime,
                'file_name' => $fallbackName !== '' ? basename($fallbackName) : (basename($urlPath) ?: null),
            ];
        }

        $cleanBase64 = str_replace(["\r", "\n", ' '], '', $source);
        $decoded = base64_decode($cleanBase64, true);

        if ($decoded !== false && $decoded !== '' && base64_encode($decoded) === $cleanBase64) {
            $resolvedMime = $this->detectMimeFromBinary($decoded, $this->pickMimeType($defaultMime, $contextInput));

            return [
                'binary' => $decoded,
                'mime_type' => $resolvedMime,
                'file_name' => $fallbackName !== '' ? basename($fallbackName) : null,
            ];
        }

        return [
            'binary' => $source,
            'mime_type' => $this->pickMimeType($defaultMime === 'application/octet-stream' ? 'text/plain' : $defaultMime, $contextInput),
            'file_name' => $fallbackName !== '' ? basename($fallbackName) : null,
        ];
    }

    public function pickSourceString(string $configuredValue, mixed $contextInput): string
    {
        $trimmed = trim($configuredValue);

        if ($trimmed !== '') {
            return $trimmed;
        }

        if (! is_array($contextInput)) {
            return '';
        }

        foreach (['data_url', 'file_uri', 'file_name', 'base64', 'data', 'url', 'file_url', 'media_url'] as $key) {
            $candidate = $contextInput[$key] ?? null;

            if (is_string($candidate) && trim($candidate) !== '') {
                if ($key === 'file_name' && ! str_starts_with(trim($candidate), 'files/')) {
                    continue;
                }

                return trim($candidate);
            }
        }

        return '';
    }

    public function isGeminiFileReference(string $source): bool
    {
        $trimmed = trim($source);

        return str_starts_with($trimmed, 'files/')
            || str_starts_with($trimmed, 'https://generativelanguage.googleapis.com/v1beta/files/')
            || str_starts_with($trimmed, 'https://generativelanguage.googleapis.com/files/');
    }

    public function toGeminiFileUri(string $source): string
    {
        $trimmed = trim($source);

        if (str_starts_with($trimmed, 'https://')) {
            return $trimmed;
        }

        $normalized = $this->normalizeFileName($trimmed);

        return "https://generativelanguage.googleapis.com/v1beta/$normalized";
    }

    public function downloadUrl(string $url): string
    {
        $downloaded = $this->downloadBinaryFromUrl($url);

        return base64_encode($downloaded['binary']);
    }

    public function downloadBinaryFromUrl(string $url): array
    {
        $cleanUrl = trim($url);
        (new SafeHttpClient())->assertSafeUrl($cleanUrl);

        try {
            $response = Http::timeout(90)->get($cleanUrl);
        } catch (Throwable $e) {
            throw NodeExecutionException::because('عدم امکان دانلود فایل از آدرس اینترنتی: ' . $e->getMessage());
        }

        if ($response->failed()) {
            throw NodeExecutionException::because("دانلود فایل از آدرس اینترنتی با کد وضعیت {$response->status()} ناموفق بود.");
        }

        $headerMime = trim(explode(';', (string) $response->header('Content-Type'))[0] ?? '');

        return [
            'binary' => $response->body(),
            'mime_type' => ($headerMime !== '' && $headerMime !== 'application/octet-stream') ? $headerMime : null,
        ];
    }

    public static function text(array $response): string
    {
        $parts = (array) data_get($response, 'candidates.0.content.parts', []);

        if ($parts === []) {
            return '';
        }

        $answerChunks = [];
        $thoughtChunks = [];

        foreach ($parts as $part) {
            if (! is_array($part) || ! isset($part['text']) || ! is_string($part['text'])) {
                continue;
            }

            if (! empty($part['thought'])) {
                $thoughtChunks[] = $part['text'];
            } else {
                $answerChunks[] = $part['text'];
            }
        }

        if ($answerChunks !== []) {
            return trim(implode("\n", $answerChunks));
        }

        return trim(implode("\n", $thoughtChunks));
    }

    public function normalizeStoreName(string $name): string
    {
        $clean = trim($name, " \t\n\r\0\x0B/");

        if ($clean === '') {
            throw NodeExecutionException::because('شناسه مخزن فایل (Store ID) الزامی است.');
        }

        if (str_starts_with($clean, 'fileSearchStores/')) {
            return $clean;
        }

        if (str_starts_with($clean, 'corpora/')) {
            return 'fileSearchStores/' . substr($clean, 8);
        }

        return "fileSearchStores/$clean";
    }

    public function normalizeFileName(string $name): string
    {
        $clean = trim($name);

        if (preg_match('#/files/([a-zA-Z0-9_-]+)#', $clean, $matches)) {
            return 'files/' . $matches[1];
        }

        $clean = ltrim($clean, '/');

        return str_starts_with($clean, 'files/') ? $clean : "files/$clean";
    }

    private function generateWithImagen(
        string $model,
        string $prompt,
        int $sampleCount,
        string $aspectRatio,
        string $outputFormat
    ): array {
        $cleanModel = $this->cleanModelName($model);

        $raw = $this->call('POST', "/models/$cleanModel:predict", [
            'instances' => [['prompt' => $prompt]],
            'parameters' => [
                'sampleCount' => $sampleCount,
                'aspectRatio' => $aspectRatio,
                'outputOptions' => ['mimeType' => $outputFormat],
            ],
        ], 120);

        $images = [];

        foreach ((array) data_get($raw, 'predictions', []) as $index => $prediction) {
            $base64 = (string) (data_get($prediction, 'bytesBase64Encoded') ?? data_get($prediction, 'b64_json') ?? '');
            if ($base64 === '') {
                continue;
            }

            $mime = (string) (data_get($prediction, 'mimeType') ?: $outputFormat);

            $images[] = [
                'index' => $index + 1,
                'mime_type' => $mime,
                'data_url' => "data:$mime;base64,$base64",
                'base64' => $base64,
            ];
        }

        if ($images === []) {
            throw NodeExecutionException::because('مدل Imagen هیچ تصویری تولید نکرد.');
        }

        return [
            'model' => $cleanModel,
            'images' => $images,
            'text' => '',
            'raw' => $raw,
        ];
    }

    private function generateWithGeminiNative(string $model, array $parts, string $defaultMime): array
    {
        $cleanModel = $this->cleanModelName($model);

        $raw = $this->generateContent(
            $cleanModel,
            [['role' => 'user', 'parts' => $parts]],
            ['responseModalities' => ['TEXT', 'IMAGE']],
        );

        $responseParts = (array) data_get($raw, 'candidates.0.content.parts', []);
        $images = [];
        $texts = [];

        foreach ($responseParts as $part) {
            if (! is_array($part)) {
                continue;
            }

            if (isset($part['inlineData']['data']) && is_string($part['inlineData']['data'])) {
                $base64 = $part['inlineData']['data'];
                $mime = (string) ($part['inlineData']['mimeType'] ?? $defaultMime);

                $images[] = [
                    'index' => count($images) + 1,
                    'mime_type' => $mime,
                    'data_url' => "data:$mime;base64,$base64",
                    'base64' => $base64,
                ];
            } elseif (isset($part['text']) && empty($part['thought'])) {
                $texts[] = (string) $part['text'];
            }
        }

        if ($images === []) {
            throw NodeExecutionException::because('مدل Gemini تصویری در خروجی برنگرداند.');
        }

        return [
            'model' => $cleanModel,
            'images' => $images,
            'text' => trim(implode("\n", $texts)),
            'raw' => $raw,
        ];
    }

    private function waitForFileActive(array $file): array
    {
        $name = (string) ($file['name'] ?? '');
        $state = strtoupper((string) ($file['state'] ?? 'ACTIVE'));

        if ($name === '' || $state === 'ACTIVE') {
            return $file;
        }

        $attempts = 0;

        while ($state === 'PROCESSING' && $attempts < 10) {
            sleep(2);
            $attempts++;

            try {
                $file = $this->getFile($name);
                $state = strtoupper((string) ($file['state'] ?? 'ACTIVE'));
            } catch (Throwable) {
                break;
            }
        }

        if ($state === 'FAILED') {
            throw NodeExecutionException::because('پردازش فایل آپلودشده در سرور Gemini با خطا مواجه شد.');
        }

        return $file;
    }

    private function waitForOperation(array $operation, int $maxWaitSeconds = 20): array
    {
        $opName = trim((string) ($operation['name'] ?? ''));
        $isDone = (bool) ($operation['done'] ?? false);

        if ($isDone || $opName === '') {
            return $operation;
        }

        $deadline = time() + $maxWaitSeconds;

        while (! $isDone && time() < $deadline) {
            sleep(2);

            try {
                $operation = $this->call('GET', '/' . ltrim($opName, '/'));
                $isDone = (bool) ($operation['done'] ?? false);
            } catch (Throwable) {
                break;
            }
        }

        if (isset($operation['error']) && is_array($operation['error'])) {
            $errMsg = (string) ($operation['error']['message'] ?? 'خطا در ایندکس کردن فایل در مخزن.');
            throw NodeExecutionException::because("خطای مخزن فایل Gemini: $errMsg");
        }

        return $operation;
    }

    private function pickMimeType(string $defaultMime, mixed $contextInput): string
    {
        if ($defaultMime !== '' && $defaultMime !== 'auto' && $defaultMime !== 'application/octet-stream') {
            return $defaultMime;
        }

        if (is_array($contextInput)) {
            foreach (['mime_type', 'mimeType', 'content_type'] as $key) {
                $val = trim((string) ($contextInput[$key] ?? ''));
                if ($val !== '' && $val !== 'application/octet-stream') {
                    return explode(';', $val)[0];
                }
            }

            $fileName = trim((string) ($contextInput['file_name'] ?? $contextInput['file_path'] ?? ''));
            if ($fileName !== '') {
                $byExt = $this->detectMimeFromExtension(pathinfo($fileName, PATHINFO_EXTENSION));
                if ($byExt !== null) {
                    return $byExt;
                }
            }
        }

        return ($defaultMime !== '' && $defaultMime !== 'auto') ? $defaultMime : 'application/pdf';
    }

    private function detectMimeFromBinary(string $binary, string $fallback = 'application/octet-stream'): string
    {
        if (str_starts_with($binary, '%PDF-')) {
            return 'application/pdf';
        }

        if (str_starts_with($binary, "\x89PNG\r\n\x1a\n")) {
            return 'image/png';
        }

        if (str_starts_with($binary, "\xFF\xD8\xFF")) {
            return 'image/jpeg';
        }

        if (str_starts_with($binary, 'GIF87a') || str_starts_with($binary, 'GIF89a')) {
            return 'image/gif';
        }

        if (str_starts_with($binary, 'RIFF') && substr($binary, 8, 4) === 'WEBP') {
            return 'image/webp';
        }

        if (str_starts_with($binary, 'OggS')) {
            return 'audio/ogg';
        }

        if (str_starts_with($binary, 'ID3') || str_starts_with($binary, "\xFF\xFB") || str_starts_with($binary, "\xFF\xF3")) {
            return 'audio/mp3';
        }

        if (str_starts_with($binary, 'RIFF') && substr($binary, 8, 4) === 'WAVE') {
            return 'audio/wav';
        }

        if (substr($binary, 4, 4) === 'ftyp') {
            return 'video/mp4';
        }

        if (str_starts_with($binary, "PK\x03\x04")) {
            return 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
        }

        return $fallback !== 'auto' && $fallback !== '' ? $fallback : 'application/octet-stream';
    }

    private function detectMimeFromExtension(string $ext): ?string
    {
        return match (strtolower(trim($ext))) {
            'pdf' => 'application/pdf',
            'txt' => 'text/plain',
            'md', 'markdown' => 'text/markdown',
            'csv' => 'text/csv',
            'html', 'htm' => 'text/html',
            'json' => 'application/json',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
            'ogg', 'oga', 'opus' => 'audio/ogg',
            'mp3' => 'audio/mp3',
            'wav' => 'audio/wav',
            'm4a' => 'audio/m4a',
            'aac' => 'audio/aac',
            'mp4' => 'video/mp4',
            'mov' => 'video/quicktime',
            'webm' => 'video/webm',
            default => null,
        };
    }

    private function cleanModelName(string $model): string
    {
        $clean = trim($model);

        return str_starts_with($clean, 'models/') ? substr($clean, 7) : $clean;
    }

    private function buildEndpoint(string $path): string
    {
        $cleanPath = '/' . ltrim($path, '/');

        if (str_ends_with($this->baseUrl, '/v1beta')) {
            return $this->baseUrl . $cleanPath;
        }

        return "$this->baseUrl/v1beta$cleanPath";
    }

    private function buildUploadEndpoint(string $path): string
    {
        $cleanPath = '/' . ltrim($path, '/');
        $root = preg_replace('#/v1beta$#', '', $this->baseUrl) ?: $this->baseUrl;

        return "$root/upload/v1beta$cleanPath";
    }

    private function checkResponse(Response $response): void
    {
        if ($response->failed()) {
            throw NodeExecutionException::because(GeminiError::describe($response));
        }
    }

    private function call(string $method, string $path, array $data = [], int $timeout = 60): array
    {
        $httpMethod = strtolower($method);
        $url = $this->buildEndpoint($path);

        try {
            $request = Http::withHeaders(['x-goog-api-key' => $this->apiKey])->timeout($timeout);

            $response = match ($httpMethod) {
                'get' => $request->get($url, $data),
                'delete' => $data !== [] ? $request->delete($url, $data) : $request->delete($url),
                default => $request->{$httpMethod}($url, $data),
            };
        } catch (Throwable $e) {
            throw NodeExecutionException::because('خطا در برقراری ارتباط با سرور Google Gemini: ' . $e->getMessage());
        }

        $this->checkResponse($response);

        return (array) $response->json();
    }
}
