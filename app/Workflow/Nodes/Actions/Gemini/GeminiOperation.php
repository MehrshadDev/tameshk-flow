<?php

namespace App\Workflow\Nodes\Actions\Gemini;

use App\Workflow\DTOs\NodeContext;
use App\Workflow\DTOs\NodeResult;
use App\Workflow\Exceptions\NodeExecutionException;

abstract class GeminiOperation implements GeminiOperationContract
{
    protected function str(NodeContext $context, string $key, string $default = ''): string
    {
        return trim((string) ($context->config[$key] ?? $default));
    }

    protected function ensure(string $value, string $message): string
    {
        if ($value === '') {
            throw NodeExecutionException::because($message);
        }

        return $value;
    }

    protected function model(NodeContext $context, string ...$keys): string
    {
        $candidates = array_unique([...$keys, 'model', 'model_multimodal']);

        foreach ($candidates as $key) {
            $value = $this->str($context, $key);
            if ($value !== '') {
                return $value;
            }
        }

        throw NodeExecutionException::because('مدل هوش مصنوعی انتخاب نشده است. لطفاً ابتدا یک مدل را از لیست انتخاب کنید.');
    }

    protected function textResult(array $answer, string $model, array $extra = []): NodeResult
    {
        return NodeResult::of([
            'text' => $answer['text'],
            'response' => $answer['text'],
            'model' => $model,
            ...$extra,
            'raw' => $answer['raw'],
        ]);
    }
}
