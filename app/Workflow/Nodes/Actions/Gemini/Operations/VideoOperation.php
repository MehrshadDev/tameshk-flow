<?php

namespace App\Workflow\Nodes\Actions\Gemini\Operations;

use App\Workflow\DTOs\NodeContext;
use App\Workflow\DTOs\NodeResult;
use App\Workflow\Nodes\Actions\Gemini\GeminiOperation;
use App\Workflow\Support\GeminiClient;

final class VideoOperation extends GeminiOperation
{
    public function handle(NodeContext $context, GeminiClient $client): NodeResult
    {
        return match ($this->str($context, 'operation_video', 'analyze_video')) {
            'download_video' => $this->download($context, $client),
            'generate_video' => $this->generate($context, $client),
            default => $this->analyze($context, $client),
        };
    }

    private function download(NodeContext $context, GeminiClient $client): NodeResult
    {
        $url = $this->ensure($this->str($context, 'video_input'), 'آدرس ویدیو الزامی است.');

        $context->log("دانلود فایل ویدیویی از $url");

        $downloaded = $client->downloadBinaryFromUrl($url);
        $base64 = base64_encode($downloaded['binary']);
        $mime = $downloaded['mime_type'] ?? 'video/mp4';

        return NodeResult::of([
            'success' => true,
            'mime_type' => $mime,
            'data_url' => "data:$mime;base64,$base64",
            'base64' => $base64,
            'video_url' => $url,
        ]);
    }

    private function generate(NodeContext $context, GeminiClient $client): NodeResult
    {
        $model = $this->model($context, 'model_video');
        $prompt = $this->ensure(
            $this->str($context, 'video_prompt') ?: $this->str($context, 'prompt'),
            'پرامپت برای تولید سناریوی ویدیو الزامی است.'
        );

        $context->log("تولید سناریو و ساختار ویدیویی با Gemini: $prompt");

        $system = 'شما یک کارگردان و تهیه‌کننده متخصص تولید ویدیو و هوش مصنوعی هستید. برای پرامپت ارائه‌شده، یک استوری‌بورد و پرامپت تولید صحنه‌های ویدیویی دقیق با زمان‌بندی، زاویه دوربین، حرکت و نورپردازی تولید کنید.';

        $answer = $client->ask($model, [['text' => "پرامپت ویدیو:\n$prompt"]], ['temperature' => 0.7], $system);

        return NodeResult::of([
            'prompt' => $prompt,
            'storyboard' => $answer['text'],
            'text' => $answer['text'],
            'response' => $answer['text'],
            'model' => $model,
            'status' => 'ready',
        ]);
    }

    private function analyze(NodeContext $context, GeminiClient $client): NodeResult
    {
        $model = $this->model($context, 'model_video');
        $video = $this->str($context, 'video_input');
        $mimeType = $this->str($context, 'video_mime_type', 'video/mp4');
        $prompt = ($this->str($context, 'video_prompt') ?: $this->str($context, 'prompt'))
            ?: 'این ویدیو را تحلیل و اتفاقات رخ‌داده در آن را تشریح کن.';

        $part = $client->resolveInputPart($video, $mimeType, $context->input);

        $context->log("تحلیل ویدیو در Gemini ($model)");

        $answer = $client->ask($model, [$part, ['text' => $prompt]], ['temperature' => 0.4]);

        return $this->textResult($answer, $model);
    }
}
