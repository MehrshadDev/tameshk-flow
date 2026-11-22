<?php

namespace App\Workflow\Nodes\Actions\Gemini\Operations;

use App\Workflow\DTOs\NodeContext;
use App\Workflow\DTOs\NodeResult;
use App\Workflow\Exceptions\NodeExecutionException;
use App\Workflow\Nodes\Actions\Gemini\GeminiOperation;
use App\Workflow\Support\GeminiClient;

final class FileSearchOperation extends GeminiOperation
{
    public function handle(NodeContext $context, GeminiClient $client): NodeResult
    {
        $operation = $this->str($context, 'operation_file_search', 'list_stores');

        return match ($operation) {
            'create_store' => $this->createStore($context, $client),
            'list_stores' => $this->listStores($context, $client),
            'upload_file_to_store' => $this->uploadToStore($context, $client),
            'query_store' => $this->queryStore($context, $client),
            'delete_store' => $this->deleteStore($context, $client),
            default => throw NodeExecutionException::because("عملیات نامعتبر مخزن فایل: $operation"),
        };
    }

    private function createStore(NodeContext $context, GeminiClient $client): NodeResult
    {
        $name = $this->ensure(
            $this->str($context, 'store_display_name'),
            'نام مخزن (Display Name) الزامی است.'
        );

        $context->log("ایجاد مخزن جستجوی فایل: $name");
        $created = $client->createFileSearchStore($name);

        return NodeResult::of([
            'success' => true,
            'store_name' => data_get($created, 'name'),
            'display_name' => data_get($created, 'displayName', $name),
            'store' => $created,
        ]);
    }

    private function listStores(NodeContext $context, GeminiClient $client): NodeResult
    {
        $context->log('دریافت لیست مخازن جستجوی فایل');
        $list = $client->listFileSearchStores(50);

        return NodeResult::of([
            'count' => count($list),
            'stores' => $list,
            'first' => $list[0] ?? null,
            'store_name' => data_get($list, '0.name'),
        ]);
    }

    private function uploadToStore(NodeContext $context, GeminiClient $client): NodeResult
    {
        $storeName = $this->ensure(
            $this->str($context, 'store_name'),
            'شناسه مخزن (Store ID) الزامی است.'
        );

        $mode = $this->str($context, 'file_input_mode');
        if ($mode === '') {
            $mode = $this->str($context, 'file_content') !== '' ? 'text' : 'binary_or_url';
        }

        $customTitle = $this->str($context, 'document_title');

        if ($mode === 'text') {
            $content = $this->ensure(
                (string) ($context->config['file_content'] ?? ''),
                'متن محتوا برای ذخیره در مخزن الزامی است.'
            );

            $title = $customTitle !== '' ? $customTitle : 'document_' . date('Ymd_His') . '.txt';
            $context->log("آپلود متن در مخزن $storeName ($title)");

            $result = $client->uploadToFileSearchStore($storeName, $content, 'text/plain', $title);

            return NodeResult::of([
                'success' => true,
                'store_name' => $result['store_name'],
                'file_name' => $result['file_name'] ?? null,
                'file_uri' => $result['file_uri'] ?? null,
                'title' => $title,
                'done' => $result['done'] ?? true,
                'result' => $result,
            ]);
        }

        $rawSource = $this->str($context, 'store_file_source');
        $preferredMime = $this->str($context, 'store_file_mime_type', 'auto');
        $source = $client->pickSourceString($rawSource, $context->input);

        if ($source === '') {
            throw NodeExecutionException::because(
                'فایلی برای آپلود در مخزن یافت نشد. مطمئن شوید نود قبلی (مثل دریافت فایل تلگرام یا HTTP) فایل خروجی تولید کرده است یا آدرس فایل را وارد کنید.'
            );
        }

        if ($client->isGeminiFileReference($source)) {
            $context->log("ایمپورت فایل $source به مخزن $storeName");
            $result = $client->importFileToSearchStore($storeName, $source);

            return NodeResult::of([
                'success' => true,
                'store_name' => $result['store_name'],
                'file_name' => $result['file_name'],
                'title' => $customTitle !== '' ? $customTitle : $result['file_name'],
                'done' => $result['done'] ?? true,
                'result' => $result,
            ]);
        }

        $extracted = $client->extractBinaryAndMime($source, $preferredMime, $context->input);
        $title = $customTitle !== ''
            ? $customTitle
            : ($extracted['file_name'] ?? ('file_' . date('Ymd_His')));

        $context->log("آپلود فایل در مخزن $storeName ($title | {$extracted['mime_type']})");

        $result = $client->uploadToFileSearchStore(
            $storeName,
            $extracted['binary'],
            $extracted['mime_type'],
            $title
        );

        return NodeResult::of([
            'success' => true,
            'store_name' => $result['store_name'],
            'file_name' => $result['file_name'] ?? null,
            'file_uri' => $result['file_uri'] ?? null,
            'mime_type' => $extracted['mime_type'],
            'title' => $title,
            'done' => $result['done'] ?? true,
            'result' => $result,
        ]);
    }

    private function queryStore(NodeContext $context, GeminiClient $client): NodeResult
    {
        $model = $this->model($context, 'model_file_search');
        $storeName = $this->ensure(
            $this->str($context, 'store_name'),
            'شناسه مخزن (Store ID) الزامی است.'
        );
        $query = $this->ensure(
            $this->str($context, 'store_query') ?: $this->str($context, 'prompt'),
            'متن پرسش از مخزن (Query) الزامی است.'
        );
        $system = $this->str($context, 'system_instruction');
        $temperature = (float) ($context->config['temperature'] ?? 0.3);

        $context->log("جستجو و پرسش از مخزن فایل $storeName با مدل $model");

        $answer = $client->queryFileSearchStore(
            $model,
            $storeName,
            $query,
            $system !== '' ? $system : null,
            $temperature,
        );

        return $this->textResult($answer, $model, [
            'store_name' => $client->normalizeStoreName($storeName),
            'grounding_metadata' => $answer['grounding_metadata'] ?? null,
        ]);
    }

    private function deleteStore(NodeContext $context, GeminiClient $client): NodeResult
    {
        $storeName = $this->ensure(
            $this->str($context, 'store_name'),
            'شناسه مخزن (Store ID) الزامی است.'
        );

        $normalized = $client->normalizeStoreName($storeName);
        $context->log("حذف مخزن فایل: $normalized");
        $client->deleteFileSearchStore($normalized, true);

        return NodeResult::of([
            'success' => true,
            'store_name' => $normalized,
        ]);
    }
}
