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
use App\Workflow\Support\DataTableManager;

class DataTableToolNode implements NodeContract, ToolContract
{
    public function __construct(
        private readonly DataTableManager $manager,
    ) {}

    public static function definition(): NodeDefinition
    {
        return new NodeDefinition(
            type: 'ai.tool.data_table',
            name: 'Data Table Tool',
            category: NodeCategory::Ai,
            description: 'ابزار خواندن، جستجو، درج و بروزرسانی ردیف‌ها در جداول داده داخلی توسط ایجنت هوش مصنوعی.',
            icon: 'table',
            group: 'tools',
            inputs: [],
            outputs: [HandleType::Tool->value],
            configSchema: [
                'tool_name' => [
                    'type'        => 'text',
                    'label'       => 'نام ابزار (انگلیسی)',
                    'default'     => 'data_table',
                    'required'    => true,
                    'placeholder' => 'data_table',
                ],
                'tool_description' => [
                    'type'     => 'textarea',
                    'label'    => 'توضیحات عملکرد ابزار برای مدل',
                    'default'  => 'مدیریت جداول داده داخلی، مشاهده لیست جداول، جستجو و دریافت ردیف‌ها، افزودن ردیف جدید و بروزرسانی یا حذف ردیف‌ها.',
                    'required' => true,
                ],
                'default_table' => [
                    'type'        => 'text',
                    'label'       => 'جدول پیش‌فرض (اختیاری)',
                    'required'    => false,
                    'placeholder' => 'در صورت خالی بودن، ایجنت خودش جدول را انتخاب می‌کند',
                ],
                'allowed_scope' => [
                    'type'     => 'select',
                    'label'    => 'دسترسی مجاز ایجنت',
                    'options'  => [
                        ['value' => 'read_write', 'label' => 'خواندن و نوشتن (جستجو، درج، ویرایش و حذف ردیف‌ها)'],
                        ['value' => 'read_only', 'label' => 'فقط خواندن و جستجو (بدون تغییر داده‌ها)'],
                    ],
                    'default'  => 'read_write',
                    'required' => true,
                ],
            ],
            color: 'teal',
        );
    }

    public function execute(NodeContext $context): NodeResult
    {
        return NodeResult::of($context->config, HandleType::Tool->value);
    }

    public function toolName(array $config): string
    {
        return trim((string) ($config['tool_name'] ?? 'data_table'));
    }

    public function toolDescription(array $config): string
    {
        $base = trim((string) ($config['tool_description'] ?? 'خواندن و ثبت ردیف در جداول داده داخلی'));
        $defaultTable = trim((string) ($config['default_table'] ?? ''));

        if ($defaultTable !== '') {
            return $base . " (جدول مقصد به‌صورت خودکار روی «{$defaultTable}» تنظیم شده است.)";
        }

        return $base;
    }

    public function toolParameters(array $config): array
    {
        $readOnly = ($config['allowed_scope'] ?? 'read_write') === 'read_only';
        $hasDefaultTable = trim((string) ($config['default_table'] ?? '')) !== '';

        $actions = $readOnly
            ? '"list_tables" (مشاهده لیست جداول)، "get_rows" (جستجو و دریافت ردیف‌ها)'
            : '"list_tables" (لیست جداول)، "get_rows" (جستجو و دریافت ردیف‌ها)، "insert_row" (درج ردیف جدید)، "update_row" (ویرایش ردیف)، "delete_row" (حذف ردیف)';

        $properties = [
            'action' => [
                'type'        => 'STRING',
                'description' => "نوع عملیات: $actions",
            ],
        ];

        if (! $hasDefaultTable) {
            $properties['table'] = [
                'type'        => 'STRING',
                'description' => 'نام جدول مورد نظر',
            ];
        }

        $properties['filter_column'] = [
            'type'        => 'STRING',
            'description' => 'نام ستون برای فیلتر کردن (مثلاً id یا phone یا email) در عملیات get_rows، update_row یا delete_row',
        ];
        $properties['filter_value'] = [
            'type'        => 'STRING',
            'description' => 'مقدار مورد نظر برای جستجو در ستون filter_column',
        ];

        if (! $readOnly) {
            $properties['row_json'] = [
                'type'        => 'STRING',
                'description' => 'آبجکت JSON ستون‌ها و مقادیر برای عملیات insert_row یا update_row؛ مثلاً {"name":"علی","phone":"09120000000"}',
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
        $user = $context->run->workflow->user;

        if (! $user) {
            throw NodeExecutionException::because('کاربر مالک ورکفلو یافت نشد.');
        }

        $readOnly = ($context->config['allowed_scope'] ?? 'read_write') === 'read_only';
        $action = strtolower(trim((string) ($arguments['action'] ?? 'get_rows')));
        $defaultTable = trim((string) ($context->config['default_table'] ?? ''));

        if ($action === 'list_tables') {
            $listed = $this->manager->listTables($user);

            if ($defaultTable !== '') {
                $filtered = array_values(array_filter(
                    $listed['tables'] ?? [],
                    fn (array $t) => ($t['name'] ?? '') === $defaultTable
                ));

                return [
                    ...$listed,
                    'count'  => count($filtered),
                    'tables' => $filtered,
                    'rows'   => $filtered,
                ];
            }

            return $listed;
        }

        $table = $defaultTable !== ''
            ? $defaultTable
            : trim((string) ($arguments['table'] ?? ''));

        if ($table === '') {
            return ['error' => 'نام جدول (table) مشخص نشده است.'];
        }

        if ($readOnly && in_array($action, ['insert_row', 'update_row', 'delete_row'], true)) {
            return ['error' => 'این ابزار روی حالت «فقط خواندن» تنظیم شده است و اجازه تغییر داده‌ها را ندارد.'];
        }

        $filterCol = trim((string) ($arguments['filter_column'] ?? ''));
        $filterVal = (string) ($arguments['filter_value'] ?? '');
        $conditions = $filterCol !== ''
            ? [['field' => $filterCol, 'operator' => 'eq', 'value' => $filterVal]]
            : [];

        return match ($action) {
            'get_rows' => $this->manager->getRows($user, $table, $conditions, 'and', 'id', 'DESC', 50),

            'insert_row' => (function () use ($user, $table, $arguments) {
                $row = json_decode((string) ($arguments['row_json'] ?? '{}'), true);
                if (! is_array($row) || $row === []) {
                    return ['error' => 'پارامتر row_json معتبر الزامی است.'];
                }
                $this->manager->createTable($user, $table, [], true);

                return $this->manager->insertRows($user, $table, [$row]);
            })(),

            'update_row' => (function () use ($user, $table, $conditions, $arguments) {
                if ($conditions === []) {
                    return ['error' => 'برای ویرایش ردیف، تعیین filter_column و filter_value الزامی است.'];
                }
                $row = json_decode((string) ($arguments['row_json'] ?? '{}'), true);
                if (! is_array($row) || $row === []) {
                    return ['error' => 'پارامتر row_json معتبر الزامی است.'];
                }

                return $this->manager->updateRows($user, $table, $row, $conditions, 'and');
            })(),

            'delete_row' => (function () use ($user, $table, $conditions) {
                if ($conditions === []) {
                    return ['error' => 'برای حذف ردیف، تعیین filter_column و filter_value الزامی است.'];
                }

                return $this->manager->deleteRows($user, $table, $conditions, 'and');
            })(),

            default => ['error' => "عملیات ناشناخته: $action"],
        };
    }
}
