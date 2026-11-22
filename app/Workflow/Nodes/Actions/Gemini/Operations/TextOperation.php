<?php

namespace App\Workflow\Nodes\Actions\Gemini\Operations;

use App\Workflow\DTOs\NodeContext;
use App\Workflow\DTOs\NodeResult;
use App\Workflow\Nodes\Actions\Gemini\GeminiOperation;
use App\Workflow\Support\GeminiClient;

final class TextOperation extends GeminiOperation
{
    public function handle(NodeContext $context, GeminiClient $client): NodeResult
    {
        $model = $this->model($context, 'model');
        $prompt = $this->ensure($this->str($context, 'prompt'), 'پرامپت ورودی نمی‌تواند خالی باشد.');
        $system = $this->str($context, 'system_instruction');
        $storeName = $this->str($context, 'file_search_store_name');

        $tools = [];
        $extra = [];

        if ($storeName !== '') {
            $normalizedStore = $client->normalizeStoreName($storeName);
            $tools[] = [
                'fileSearch' => [
                    'fileSearchStoreNames' => [$normalizedStore],
                ],
            ];
            $extra['store_name'] = $normalizedStore;
        }

        $context->log("ارسال پرامپت متنی به Gemini ($model)");

        $answer = $client->ask(
            $model,
            [['text' => $prompt]],
            ['temperature' => (float) ($context->config['temperature'] ?? 0.7)],
            $system !== '' ? $system : null,
            $tools,
        );

        if (isset($answer['grounding_metadata'])) {
            $extra['grounding_metadata'] = $answer['grounding_metadata'];
        }

        return $this->textResult($answer, $model, $extra);
    }
}
