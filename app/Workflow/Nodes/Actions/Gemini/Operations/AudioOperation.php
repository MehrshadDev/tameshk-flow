<?php

namespace App\Workflow\Nodes\Actions\Gemini\Operations;

use App\Workflow\DTOs\NodeContext;
use App\Workflow\DTOs\NodeResult;
use App\Workflow\Nodes\Actions\Gemini\GeminiOperation;
use App\Workflow\Support\GeminiClient;

final class AudioOperation extends GeminiOperation
{
    public function handle(NodeContext $context, GeminiClient $client): NodeResult
    {
        $model = $this->model($context, 'model_multimodal');
        $operation = $this->str($context, 'operation_audio', 'analyze_audio');
        $rawAudio = $this->str($context, 'audio_input');
        $mimeType = $this->str($context, 'audio_mime_type', 'audio/ogg');

        $part = $client->resolveInputPart($rawAudio, $mimeType, $context->input);

        $customPrompt = $this->str($context, 'audio_prompt') ?: $this->str($context, 'prompt');

        $prompt = $operation === 'transcribe_audio'
            ? ($customPrompt !== ''
                ? $customPrompt
                : 'لطفاً تمام کلمات بیان‌شده در این فایل صوتی را با دقت بالا، بدون کم‌وکاست و به صورت رونوشت کلمه‌به‌کلمه پیاده‌سازی کن.')
            : ($customPrompt !== ''
                ? $customPrompt
                : 'محتوای این فایل صوتی را تحلیل و خلاصه‌سازی کن.');

        $context->log("پردازش فایل صوتی در Gemini ($model)");

        $answer = $client->ask(
            $model,
            [$part, ['text' => $prompt]],
            ['temperature' => (float) ($context->config['temperature'] ?? 0.2)],
        );

        return $this->textResult($answer, $model, [
            'transcription' => $answer['text'],
            'operation' => $operation,
        ]);
    }
}
