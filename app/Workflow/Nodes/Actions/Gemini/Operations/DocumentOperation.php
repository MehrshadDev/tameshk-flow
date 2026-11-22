<?php

namespace App\Workflow\Nodes\Actions\Gemini\Operations;

use App\Workflow\DTOs\NodeContext;
use App\Workflow\DTOs\NodeResult;
use App\Workflow\Nodes\Actions\Gemini\GeminiOperation;
use App\Workflow\Support\GeminiClient;

final class DocumentOperation extends GeminiOperation
{
    public function handle(NodeContext $context, GeminiClient $client): NodeResult
    {
        $model = $this->model($context, 'model');
        $rawDocument = $this->str($context, 'document_input');
        $mimeType = $this->str($context, 'document_mime_type', 'application/pdf');
        $prompt = $this->str($context, 'prompt', 'این سند را بررسی و نکات مهم آن را خلاصه کن.');
        $system = $this->str($context, 'system_instruction');

        $part = $client->resolveInputPart($rawDocument, $mimeType, $context->input);

        $context->log("تحلیل سند توسط Gemini ($model)");

        $answer = $client->ask(
            $model,
            [$part, ['text' => $prompt]],
            ['temperature' => (float) ($context->config['temperature'] ?? 0.3)],
            $system !== '' ? $system : null,
        );

        return $this->textResult($answer, $model);
    }
}
