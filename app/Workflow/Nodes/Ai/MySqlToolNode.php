<?php

namespace App\Workflow\Nodes\Ai;

use App\Workflow\Contracts\NodeContract;
use App\Workflow\Contracts\ToolContract;
use App\Workflow\DTOs\NodeContext;
use App\Workflow\DTOs\NodeDefinition;
use App\Workflow\DTOs\NodeResult;
use App\Workflow\Enums\HandleType;
use App\Workflow\Enums\NodeCategory;
use App\Workflow\Exceptions\NodeExecutionException;
use App\Workflow\Support\MySqlClient;

class MySqlToolNode implements NodeContract, ToolContract
{
    public static function definition(): NodeDefinition
    {
        return new NodeDefinition(
            type: 'ai.tool.mysql',
            name: 'MySQL Tool',
            category: NodeCategory::Ai,
            description: 'ابزار جستجو، تحلیل جداول و اجرای کوئری در پایگاه داده MySQL توسط ایجنت هوش مصنوعی.',
            icon: 'brand:mysql',
            group: 'tools',
            inputs: [],
            outputs: [HandleType::Tool->value],
            configSchema: [
                'credential_id' => [
                    'type'            => 'credential',
                    'credential_type' => 'mysql',
                    'label'           => 'اعتبارنامه MySQL',
                    'required'        => true,
                ],
                'tool_name' => [
                    'type'        => 'text',
                    'label'       => 'نام ابزار (انگلیسی)',
                    'default'     => 'mysql_database',
                    'required'    => true,
                    'placeholder' => 'mysql_database',
                ],
                'tool_description' => [
                    'type'     => 'textarea',
                    'label'    => 'توضیحات عملکرد ابزار برای مدل',
                    'default'  => 'مشاهده لیست جداول، جستجو و خواندن رکوردها یا اجرای کوئری SQL در پایگاه داده MySQL.',
                    'required' => true,
                ],
                'allowed_scope' => [
                    'type'     => 'select',
                    'label'    => 'سطح دسترسی ایجنت (Access Mode)',
                    'options'  => [
                        ['value' => 'read_only', 'label' => 'فقط خواندن و گزارش‌گیری (SELECT / SHOW / DESCRIBE - پیشنهادی)'],
                        ['value' => 'read_write', 'label' => 'خواندن و نوشتن (امکان درج و ویرایش رکوردها)'],
                    ],
                    'default'  => 'read_only',
                    'required' => true,
                ],
            ],
            color: 'sky',
        );
    }

    public function execute(NodeContext $context): NodeResult
    {
        return NodeResult::of($context->config, HandleType::Tool->value);
    }

    public function toolName(array $config): string
    {
        return trim((string) ($config['tool_name'] ?? 'mysql_database'));
    }

    public function toolDescription(array $config): string
    {
        return trim((string) ($config['tool_description'] ?? 'جستجو و تعامل با پایگاه داده MySQL'));
    }

    public function toolParameters(array $config): array
    {
        $readOnly = ($config['allowed_scope'] ?? 'read_only') === 'read_only';

        $actionsDesc = $readOnly
            ? '"list_tables" (مشاهده لیست جداول)، "describe_table" (مشاهده ستون‌های یک جدول)، "query" (اجرای کوئری SELECT)'
            : '"list_tables" (مشاهده لیست جداول)، "describe_table" (مشاهده ستون‌های یک جدول)، "query" (اجرای کوئری SQL)، "insert" (درج رکورد در جدول)';

        $properties = [
            'action' => [
                'type'        => 'STRING',
                'description' => "نوع عملیات: {$actionsDesc}",
            ],
            'table' => [
                'type'        => 'STRING',
                'description' => 'نام جدول مورد نظر (برای describe_table یا insert)',
            ],
            'sql' => [
                'type'        => 'STRING',
                'description' => 'دستور SQL با علامت ? برای پارامترها (برای عملیات query)',
            ],
            'parameters_json' => [
                'type'        => 'STRING',
                'description' => 'آرایه JSON مقادیر جایگزین علامت‌های ? در کوئری؛ مثلاً ["active", 10]',
            ],
        ];

        if (! $readOnly) {
            $properties['row_json'] = [
                'type'        => 'STRING',
                'description' => 'آبجکت JSON ستون‌ها و مقادیر برای درج در جدول (در عملیات insert)',
            ];
        }

        return [
            'type'       => 'OBJECT',
            'properties' => $properties,
            'required'   => ['action'],
        ];
    }

    public function executeTool(NodeContext $context, array $arguments): mixed
    {
        $credential = $context->credential();

        if (! $credential) {
            throw NodeExecutionException::because('اعتبارنامه MySQL برای ابزار ایجنت انتخاب نشده است.');
        }

        $client = new MySqlClient($credential);
        $readOnly = ($context->config['allowed_scope'] ?? 'read_only') === 'read_only';
        $action = strtolower(trim((string) ($arguments['action'] ?? 'list_tables')));

        return match ($action) {
            'list_tables' => [
                'tables' => array_column($client->listTables(), 'value'),
            ],

            'describe_table' => (function () use ($client, $arguments) {
                $table = trim((string) ($arguments['table'] ?? ''));
                $quoted = $client->quoteTable($table);

                return $client->executeQuery("DESCRIBE {$quoted}");
            })(),

            'query' => (function () use ($client, $arguments, $readOnly) {
                $sql = trim((string) ($arguments['sql'] ?? ''));
                if ($sql === '') {
                    return ['error' => 'پارامتر sql الزامی است.'];
                }

                if ($readOnly && ! preg_match('/^\s*(SELECT|SHOW|DESCRIBE|DESC|EXPLAIN)\b/i', $sql)) {
                    return ['error' => 'این ابزار روی حالت «فقط خواندن» تنظیم شده است و فقط کوئری‌های SELECT، SHOW و DESCRIBE مجاز هستند.'];
                }

                $rawParams = trim((string) ($arguments['parameters_json'] ?? ''));
                $params = [];
                if ($rawParams !== '') {
                    $decoded = json_decode($rawParams, true);
                    if (is_array($decoded)) {
                        $params = array_values($decoded);
                    }
                }

                return $client->executeQuery($sql, $params);
            })(),

            'insert' => (function () use ($client, $arguments, $readOnly) {
                if ($readOnly) {
                    return ['error' => 'این ابزار روی حالت «فقط خواندن» تنظیم شده و اجازه درج داده ندارد.'];
                }

                $table = trim((string) ($arguments['table'] ?? ''));
                $row = json_decode((string) ($arguments['row_json'] ?? '{}'), true);

                if (! is_array($row) || $row === []) {
                    return ['error' => 'پارامتر row_json معتبر الزامی است.'];
                }

                return $client->insertRows($table, [$row]);
            })(),

            default => ['error' => "عملیات ناشناخته: {$action}"],
        };
    }
}
