<?php

namespace App\Workflow\Nodes\Actions\Gemini\Operations;

use App\Workflow\DTOs\NodeContext;
use App\Workflow\DTOs\NodeResult;
use App\Workflow\Nodes\Actions\Gemini\GeminiOperation;
use App\Workflow\Support\GeminiClient;

final class ImageOperation extends GeminiOperation
{
    public function handle(NodeContext $context, GeminiClient $client): NodeResult
    {
        $operation = $this->str($context, 'operation_image', 'analyze_image');

        return match ($operation) {
            'generate_image' => $this->generate($context, $client),
            'edit_image' => $this->edit($context, $client),
            default => $this->analyze($context, $client),
        };
    }

    private function generate(NodeContext $context, GeminiClient $client): NodeResult
    {
        $prompt = $this->ensure($this->str($context, 'prompt'), 'پرامپت برای تولید تصویر الزامی است.');
        $outputFormat = $this->str($context, 'output_format', 'image/png');

        $context->log("تولید تصویر با Gemini: $prompt");

        $result = $client->generateImages($prompt, [
            'model' => $this->str($context, 'image_generation_model', 'auto'),
            'aspect_ratio' => $this->str($context, 'aspect_ratio', '1:1'),
            'number_of_images' => (int) ($context->config['number_of_images'] ?? 1),
            'output_format' => $outputFormat,
        ]);

        $images = $result['images'];

        return NodeResult::of([
            'success' => true,
            'operation' => 'generate_image',
            'model' => $result['model'],
            'count' => count($images),
            'images' => $images,
            'first' => $images[0] ?? null,
            'data_url' => $images[0]['data_url'] ?? '',
            'base64' => $images[0]['base64'] ?? '',
            'mime_type' => $images[0]['mime_type'] ?? $outputFormat,
            'text' => $result['text'] ?? '',
            'prompt' => $prompt,
        ]);
    }

    private function edit(NodeContext $context, GeminiClient $client): NodeResult
    {
        $prompt = $this->ensure($this->str($context, 'prompt'), 'پرامپت تغییرات تصویر الزامی است.');
        $rawImage = $this->str($context, 'image_input');
        $outputFormat = $this->str($context, 'output_format', 'image/png');

        $imagePart = $client->resolveInputPart($rawImage, 'image/jpeg', $context->input);

        $context->log("ویرایش تصویر در Gemini: $prompt");

        $result = $client->editImage($prompt, $imagePart, [
            'model' => $this->str($context, 'image_generation_model', 'auto'),
            'output_format' => $outputFormat,
        ]);

        $images = $result['images'];

        return NodeResult::of([
            'success' => true,
            'operation' => 'edit_image',
            'model' => $result['model'],
            'count' => count($images),
            'images' => $images,
            'first' => $images[0] ?? null,
            'data_url' => $images[0]['data_url'] ?? '',
            'base64' => $images[0]['base64'] ?? '',
            'mime_type' => $images[0]['mime_type'] ?? $outputFormat,
            'text' => $result['text'] ?? '',
            'prompt' => $prompt,
        ]);
    }

    private function analyze(NodeContext $context, GeminiClient $client): NodeResult
    {
        $model = $this->model($context, 'model_image');
        $rawImage = $this->str($context, 'image_input');
        $prompt = $this->str($context, 'prompt') ?: 'این تصویر را با جزئیات کامل توصیف و تحلیل کن.';

        $imagePart = $client->resolveInputPart($rawImage, 'image/jpeg', $context->input);

        $context->log("تحلیل تصویر در Gemini ($model)");

        $answer = $client->ask(
            $model,
            [$imagePart, ['text' => $prompt]],
            ['temperature' => (float) ($context->config['temperature'] ?? 0.4)],
        );

        return $this->textResult($answer, $model, ['operation' => 'analyze_image']);
    }
}
