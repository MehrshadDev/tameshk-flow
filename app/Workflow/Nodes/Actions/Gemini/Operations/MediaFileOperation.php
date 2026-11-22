<?php

namespace App\Workflow\Nodes\Actions\Gemini\Operations;

use App\Workflow\DTOs\NodeContext;
use App\Workflow\DTOs\NodeResult;
use App\Workflow\Nodes\Actions\Gemini\GeminiOperation;
use App\Workflow\Support\GeminiClient;

final class MediaFileOperation extends GeminiOperation
{
    public function handle(NodeContext $context, GeminiClient $client): NodeResult
    {
        $rawSource = $this->str($context, 'media_source');
        $preferredMime = $this->str($context, 'media_mime_type', 'auto');
        $customName = $this->str($context, 'media_display_name');

        $extracted = $client->extractBinaryAndMime($rawSource, $preferredMime, $context->input);
        $displayName = $customName !== '' ? $customName : ($extracted['file_name'] ?? null);

        $context->log("آپلود فایل در Gemini Files API ({$extracted['mime_type']})");

        $uploaded = $client->uploadMedia($extracted['binary'], $extracted['mime_type'], $displayName);

        return NodeResult::of([
            'success' => true,
            'file_name' => data_get($uploaded, 'name'),
            'file_uri' => data_get($uploaded, 'uri'),
            'mime_type' => data_get($uploaded, 'mimeType', $extracted['mime_type']),
            'size' => data_get($uploaded, 'sizeBytes', strlen($extracted['binary'])),
            'state' => data_get($uploaded, 'state', 'ACTIVE'),
            'file' => $uploaded,
        ]);
    }
}
