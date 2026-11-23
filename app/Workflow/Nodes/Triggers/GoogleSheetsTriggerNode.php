<?php

namespace App\Workflow\Nodes\Triggers;

use App\Models\Credential;
use App\Models\Node;
use App\Models\Workflow;
use App\Workflow\Contracts\NodeContract;
use App\Workflow\Contracts\PollingTriggerContract;
use App\Workflow\DTOs\NodeContext;
use App\Workflow\DTOs\NodeDefinition;
use App\Workflow\DTOs\NodeResult;
use App\Workflow\Enums\NodeCategory;
use App\Workflow\Exceptions\NodeExecutionException;
use App\Workflow\Support\GoogleSheetsClient;
use App\Workflow\Support\GoogleSheetsConfig;
use Illuminate\Support\Facades\Cache;
use Throwable;

abstract class GoogleSheetsTriggerNode implements NodeContract, PollingTriggerContract
{
    private const LISTEN_SECONDS = 60;
    private const POLL_INTERVAL = 3;
    private const RANGE = 'A1:Z5000';

    abstract protected static function watch(): string;

    abstract protected static function typeName(): string;

    abstract protected static function displayName(): string;

    abstract protected static function description(): string;

    public static function definition(): NodeDefinition
    {
        return new NodeDefinition(
            type: static::typeName(),
            name: static::displayName(),
            category: NodeCategory::Trigger,
            description: static::description(),
            icon: 'brand:google-sheets',
            group: 'google-sheets',
            inputs: [],
            outputs: ['main'],
            configSchema: array_merge(
                [
                    'credential_id' => [
                        'type'            => 'credential',
                        'credential_type' => 'google',
                        'label'           => 'حساب گوگل',
                        'required'        => true,
                    ],
                ],
                GoogleSheetsConfig::schema(),
                [
                    'has_header' => [
                        'type'     => 'select',
                        'label'    => 'فرمت خروجی سطرها',
                        'options'  => [
                            ['value' => '1', 'label' => 'ستون‌های نام‌گذاری‌شده (بر اساس سطر اول شیت)'],
                            ['value' => '0', 'label' => 'لیست ساده مقادیر (بدون عنوان ستون)'],
                        ],
                        'default'  => '1',
                        'advanced' => true,
                    ],
                ],
            ),
            color: 'emerald',
        );
    }

    public function poll(Workflow $workflow, Node $trigger, ?Credential $credential): array
    {
        if (! $credential) {
            return [];
        }

        $config = is_array($trigger->config) ? $trigger->config : [];
        $client = new GoogleSheetsClient($credential);
        $spreadsheetId = GoogleSheetsConfig::resolveSpreadsheet($config);
        $range = GoogleSheetsConfig::rangeFor($config, null, self::RANGE);
        $cacheKey = $this->cacheKey($workflow->id, $trigger->id, $spreadsheetId, $range);

        $snapshot = $this->snapshot($client, $spreadsheetId, $range);
        $baseline = Cache::get($cacheKey);

        if (! is_array($baseline)) {
            Cache::put($cacheKey, $this->state($snapshot), now()->addDays(14));

            return [];
        }

        $change = $this->detect($baseline, $snapshot, static::watch());

        if ($change === null) {
            Cache::put($cacheKey, $baseline, now()->addDays(14));

            return [];
        }

        Cache::put($cacheKey, $this->state($snapshot), now()->addDays(14));
        $payload = $this->formatPayload($config, $snapshot, $change, $spreadsheetId, $range);

        if ($payload['count'] === 0) {
            return [];
        }

        $credential->markAsUsed();

        return [$payload];
    }

    public function execute(NodeContext $context): NodeResult
    {
        $input = is_array($context->input) ? $context->input : [];

        if (isset($input['row']) || isset($input['rows'])) {
            return NodeResult::of($input);
        }

        $credential = $context->credential();

        if (! $credential) {
            throw NodeExecutionException::because('برای تست این مرحله، انتخاب اعتبارنامه حساب گوگل الزامی است.');
        }

        $client = new GoogleSheetsClient($credential);
        $spreadsheetId = GoogleSheetsConfig::resolveSpreadsheet($context);
        $range = GoogleSheetsConfig::rangeFor($context, null, self::RANGE);
        $watch = static::watch();

        $cacheKey = $this->cacheKey($context->run->workflow_id, $context->node->id, $spreadsheetId, $range);
        $cancelKey = "cancel_workflow_run_{$context->run->id}";
        $deadline = microtime(true) + self::LISTEN_SECONDS;
        $baseline = Cache::get($cacheKey);

        $context->log("در حال بررسی و انتظار برای تغییر در شیت ({$range})...");

        try {
            while (microtime(true) < $deadline) {
                if (Cache::get($cancelKey)) {
                    throw NodeExecutionException::because('انتظار برای تغییر شیت متوقف شد.');
                }

                $snapshot = $this->snapshot($client, $spreadsheetId, $range);

                if (! is_array($baseline)) {
                    $baseline = $snapshot;
                    Cache::put($cacheKey, $this->state($baseline), now()->addDays(14));

                    sleep(self::POLL_INTERVAL);
                    continue;
                }

                $change = $this->detect($baseline, $snapshot, $watch);

                if ($change !== null) {
                    Cache::put($cacheKey, $this->state($snapshot), now()->addDays(14));

                    return NodeResult::of(
                        $this->formatPayload($context->config, $snapshot, $change, $spreadsheetId, $range)
                    );
                }

                sleep(self::POLL_INTERVAL);
            }

            throw NodeExecutionException::because('در مدت ۶۰ ثانیه تغییر جدیدی در شیت ثبت نشد. یک ردیف در شیت اضافه یا ویرایش کنید و دوباره تست را بزنید.');
        } catch (NodeExecutionException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw NodeExecutionException::because('خطا در بررسی شیت: ' . $e->getMessage());
        }
    }

    private function cacheKey(int $workflowId, int $nodeId, string $spreadsheetId, string $range): string
    {
        return "sheets_trigger_{$workflowId}_{$nodeId}_" . md5("$spreadsheetId|$range");
    }

    private function snapshot(GoogleSheetsClient $client, string $spreadsheetId, string $range): array
    {
        $response = $client->getValues($spreadsheetId, $range);
        $values = array_values((array) ($response['values'] ?? []));

        return [
            'values' => $values,
            'count'  => count($values),
            'hashes' => array_map(fn (array $row) => md5(json_encode($row, JSON_UNESCAPED_UNICODE)), $values),
        ];
    }

    private function state(array $snapshot): array
    {
        return [
            'count'  => $snapshot['count'],
            'hashes' => $snapshot['hashes'],
            'at'     => now()->toIso8601String(),
        ];
    }

    private function detect(array $baseline, array $snapshot, string $watch): ?array
    {
        $added = $snapshot['count'] > (int) ($baseline['count'] ?? 0);
        $changed = [];

        $limit = min($snapshot['count'], count($baseline['hashes'] ?? []));
        for ($index = 0; $index < $limit; $index++) {
            if (($baseline['hashes'][$index] ?? null) !== ($snapshot['hashes'][$index] ?? null)) {
                $changed[] = $index;
            }
        }

        if ($watch !== 'updated' && $added) {
            $indexes = [];
            $start = (int) ($baseline['count'] ?? 0);

            for ($index = $start; $index < $snapshot['count']; $index++) {
                $indexes[] = $index;
            }

            return ['type' => 'added', 'indexes' => $indexes];
        }

        if ($watch !== 'added' && $changed !== []) {
            return ['type' => 'updated', 'indexes' => $changed];
        }

        return null;
    }

    private function formatPayload(
        array $config,
        array $snapshot,
        array $change,
        string $spreadsheetId,
        string $range,
    ): array {
        $values = $snapshot['values'];
        $withHeader = ((string) ($config['has_header'] ?? '1')) === '1';
        $headers = $withHeader ? GoogleSheetsConfig::normalizeRow($values[0] ?? []) : [];
        $rows = [];

        foreach ($change['indexes'] as $index) {
            if ($withHeader && $index === 0) {
                continue;
            }

            $row = $values[$index] ?? null;

            if ($row === null) {
                continue;
            }

            $normalized = GoogleSheetsConfig::normalizeRow($row);
            $rows[] = $headers !== [] ? GoogleSheetsConfig::combineRow($headers, $normalized) : $normalized;
        }

        $first = $rows[0] ?? null;

        return [
            'event'          => $change['type'],
            'spreadsheet_id' => $spreadsheetId,
            'range'          => $range,
            'headers'        => $headers,
            'rows'           => $rows,
            'row'            => $first,
            'row_number'     => isset($change['indexes'][0]) ? $change['indexes'][0] + 1 : null,
            'count'          => count($rows),
        ];
    }
}
