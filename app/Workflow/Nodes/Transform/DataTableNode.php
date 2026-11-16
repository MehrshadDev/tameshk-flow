<?php

namespace App\Workflow\Nodes\Transform;

use App\Workflow\Contracts\NodeContract;
use App\Workflow\DTOs\NodeContext;
use App\Workflow\DTOs\NodeDefinition;
use App\Workflow\DTOs\NodeResult;
use App\Workflow\Enums\HandleType;
use App\Workflow\Enums\NodeCategory;
use App\Workflow\Exceptions\NodeExecutionException;
use App\Workflow\Support\DataTableManager;

class DataTableNode implements NodeContract
{
    public function __construct(
        private readonly DataTableManager $manager,
    ) {}

    public static function definition(): NodeDefinition
    {
        return new NodeDefinition(
            type: 'transform.data_table',
            name: 'Data table',
            category: NodeCategory::Transform,
            description: 'ذخیره، جستجو، بروزرسانی و مدیریت جداول داده داخلی بدون نیاز به دیتابیس خارجی.',
            icon: 'table',
            group: 'data-table',
            inputs: [HandleType::Main->value],
            outputs: [HandleType::Main->value],
            configSchema: [
                'operation' => [
                    'type'     => 'select',
                    'label'    => 'عملیات (Operation)',
                    'options'  => [
                        ['value' => 'delete_rows', 'label' => 'حذف ردیف‌ها (Delete row(s))'],
                        ['value' => 'get_rows', 'label' => 'دریافت و جستجوی ردیف‌ها (Get row(s))'],
                        ['value' => 'if_row_exists', 'label' => 'ادامه در صورت وجود ردیف (If row exists)'],
                        ['value' => 'if_row_not_exists', 'label' => 'ادامه در صورت عدم وجود ردیف (If row does not exist)'],
                        ['value' => 'insert_row', 'label' => 'درج ردیف جدید (Insert row)'],
                        ['value' => 'update_rows', 'label' => 'بروزرسانی ردیف‌ها (Update row(s))'],
                        ['value' => 'upsert_rows', 'label' => 'درج یا بروزرسانی ردیف‌ها (Upsert row(s))'],
                        ['value' => 'clear_table', 'label' => 'خالی کردن جدول داده (Clear a data table)'],
                        ['value' => 'create_table', 'label' => 'ساخت جدول داده جدید (Create a data table)'],
                        ['value' => 'delete_table', 'label' => 'حذف کامل جدول داده (Delete a data table)'],
                        ['value' => 'list_tables', 'label' => 'دریافت لیست جدول‌ها (List data tables)'],
                        ['value' => 'rename_table', 'label' => 'تغییر نام جدول داده (Rename a data table)'],
                    ],
                    'default'  => 'insert_row',
                    'required' => true,
                ],

                'table' => [
                    'type'        => 'text',
                    'label'       => 'نام جدول (Data Table)',
                    'required'    => true,
                    'default'     => 'users',
                    'placeholder' => 'مثلاً: users یا customers یا سفارش‌ها',
                    'help'        => 'اگر جدول وجود نداشته باشد، هنگام درج ردیف به صورت خودکار ساخته می‌شود.',
                    'depends_on'  => [
                        'operation' => [
                            'delete_rows',
                            'get_rows',
                            'if_row_exists',
                            'if_row_not_exists',
                            'insert_row',
                            'update_rows',
                            'upsert_rows',
                            'clear_table',
                            'create_table',
                            'delete_table',
                            'rename_table',
                        ],
                    ],
                ],

                'new_table_name' => [
                    'type'        => 'text',
                    'label'       => 'نام جدید جدول (New Name)',
                    'required'    => true,
                    'placeholder' => 'نام جدید جدول را وارد کنید',
                    'depends_on'  => ['operation' => 'rename_table'],
                ],

                'combinator' => [
                    'type'       => 'select',
                    'label'      => 'ترکیب شروط (Combine Conditions)',
                    'options'    => [
                        ['value' => 'and', 'label' => 'برقرار بودن همه شروط (AND)'],
                        ['value' => 'or', 'label' => 'برقرار بودن حداقل یک شرط (OR)'],
                    ],
                    'default'    => 'and',
                    'depends_on' => [
                        'operation' => [
                            'delete_rows',
                            'get_rows',
                            'if_row_exists',
                            'if_row_not_exists',
                            'update_rows',
                            'upsert_rows',
                        ],
                    ],
                ],
                'conditions' => [
                    'type'       => 'conditions',
                    'label'      => 'شروط تطابق ردیف (Filter Conditions)',
                    'default'    => [],
                    'help'       => 'در «مقدار اول» نام ستون جدول (مثلاً id یا phone) و در «مقدار دوم» مقدار مورد نظر را وارد کنید.',
                    'depends_on' => [
                        'operation' => [
                            'delete_rows',
                            'get_rows',
                            'if_row_exists',
                            'if_row_not_exists',
                            'update_rows',
                            'upsert_rows',
                        ],
                    ],
                ],

                'data_mode' => [
                    'type'       => 'select',
                    'label'      => 'نحوه ورود داده‌های ردیف (Data Mode)',
                    'options'    => [
                        ['value' => 'manual', 'label' => 'نگاشت دستی ستون‌ها (Define Below)'],
                        ['value' => 'json', 'label' => 'آرایه یا آبجکت JSON'],
                    ],
                    'default'    => 'manual',
                    'required'   => true,
                    'depends_on' => [
                        'operation' => ['insert_row', 'update_rows', 'upsert_rows'],
                    ],
                ],
                'row_fields' => [
                    'type'       => 'set_fields',
                    'label'      => 'ستون‌ها و مقادیر (Columns & Values)',
                    'default'    => [],
                    'help'       => 'نام هر ستون و مقدار آن را تعیین کنید (می‌توانید فیلدهای ورودی را به اینجا بکشید).',
                    'depends_on' => [
                        'operation' => ['insert_row', 'update_rows', 'upsert_rows'],
                        'data_mode' => 'manual',
                    ],
                ],
                'json_data' => [
                    'type'        => 'code',
                    'label'       => 'داده‌های JSON (JSON Object / Rows)',
                    'default'     => "{\n  \"name\": \"Ali\",\n  \"phone\": \"09120000000\"\n}",
                    'placeholder' => "{\n  \"name\": \"Ali\",\n  \"phone\": \"09120000000\"\n}",
                    'depends_on'  => [
                        'operation' => ['insert_row', 'update_rows', 'upsert_rows'],
                        'data_mode' => 'json',
                    ],
                ],

                'sort_column' => [
                    'type'        => 'text',
                    'label'       => 'مرتب‌سازی بر اساس ستون (Sort By)',
                    'default'     => 'id',
                    'placeholder' => 'id',
                    'advanced'    => true,
                    'depends_on'  => ['operation' => 'get_rows'],
                ],
                'sort_direction' => [
                    'type'       => 'select',
                    'label'      => 'جهت مرتب‌سازی (Sort Direction)',
                    'options'    => [
                        ['value' => 'DESC', 'label' => 'نزولی - جدیدترین‌ها (DESC)'],
                        ['value' => 'ASC', 'label' => 'صعودی - قدیمی‌ترین‌ها (ASC)'],
                    ],
                    'default'    => 'DESC',
                    'advanced'   => true,
                    'depends_on' => ['operation' => 'get_rows'],
                ],
                'limit' => [
                    'type'       => 'number',
                    'label'      => 'حداکثر تعداد ردیف (Limit)',
                    'default'    => 100,
                    'min'        => 1,
                    'max'        => 1000,
                    'integer'    => true,
                    'advanced'   => true,
                    'depends_on' => ['operation' => 'get_rows'],
                ],
            ],
            presets: [
                [
                    'key'         => 'delete_rows',
                    'name'        => 'Delete row(s)',
                    'description' => 'حذف یک یا چند ردیف منطبق با شرط از جدول داده.',
                    'config'      => ['operation' => 'delete_rows'],
                ],
                [
                    'key'         => 'get_rows',
                    'name'        => 'Get row(s)',
                    'description' => 'جستجو، فیلتر و دریافت ردیف‌ها از جدول داده.',
                    'config'      => ['operation' => 'get_rows'],
                ],
                [
                    'key'         => 'if_row_exists',
                    'name'        => 'If row exists',
                    'description' => 'ادامه مسیر اجرای ورکفلو تنها در صورتی که ردیف منطبق با شرط در جدول وجود داشته باشد.',
                    'config'      => ['operation' => 'if_row_exists'],
                ],
                [
                    'key'         => 'if_row_not_exists',
                    'name'        => 'If row does not exist',
                    'description' => 'ادامه مسیر اجرای ورکفلو تنها در صورتی که ردیف منطبق با شرط در جدول وجود نداشته باشد.',
                    'config'      => ['operation' => 'if_row_not_exists'],
                ],
                [
                    'key'         => 'insert_row',
                    'name'        => 'Insert row',
                    'description' => 'افزودن ردیف جدید به جدول داده (با ساخت خودکار جدول و ستون‌ها).',
                    'config'      => ['operation' => 'insert_row'],
                ],
                [
                    'key'         => 'update_rows',
                    'name'        => 'Update row(s)',
                    'description' => 'بروزرسانی مقادیر ستون‌ها برای ردیف‌های منطبق با شرط.',
                    'config'      => ['operation' => 'update_rows'],
                ],
                [
                    'key'         => 'upsert_rows',
                    'name'        => 'Upsert row(s)',
                    'description' => 'بروزرسانی ردیف در صورت وجود داشتن، یا درج ردیف جدید در صورت عدم وجود.',
                    'config'      => ['operation' => 'upsert_rows'],
                ],
                [
                    'key'         => 'clear_table',
                    'name'        => 'Clear a data table',
                    'description' => 'پاک‌کردن تمام ردیف‌های یک جدول داده بدون حذف خود جدول.',
                    'config'      => ['operation' => 'clear_table'],
                ],
                [
                    'key'         => 'create_table',
                    'name'        => 'Create a data table',
                    'description' => 'ایجاد یک جدول داده جدید در حساب کاربری.',
                    'config'      => ['operation' => 'create_table'],
                ],
                [
                    'key'         => 'delete_table',
                    'name'        => 'Delete a data table',
                    'description' => 'حذف کامل یک جدول داده و تمام ردیف‌های داخل آن.',
                    'config'      => ['operation' => 'delete_table'],
                ],
                [
                    'key'         => 'list_tables',
                    'name'        => 'List data tables',
                    'description' => 'دریافت فهرست تمام جداول داده ساخته‌شده، ستون‌ها، تعداد ردیف‌ها و حجم مصرفی.',
                    'config'      => ['operation' => 'list_tables'],
                ],
                [
                    'key'         => 'rename_table',
                    'name'        => 'Rename a data table',
                    'description' => 'تغییر نام یک جدول داده موجود.',
                    'config'      => ['operation' => 'rename_table'],
                ],
            ],
            color: 'teal',
        );
    }

    public function execute(NodeContext $context): NodeResult
    {
        $user = $context->run->workflow->user;

        if (! $user) {
            throw NodeExecutionException::because('مالک ورکفلو برای دسترسی به جداول داده یافت نشد.');
        }

        $operation = (string) ($context->config['operation'] ?? 'insert_row');

        return match ($operation) {
            'list_tables'       => NodeResult::of($this->manager->listTables($user)),
            'create_table'      => NodeResult::of($this->manager->createTable($user, $this->requireTable($context), [], true)),
            'rename_table'      => $this->handleRenameTable($context, $user),
            'clear_table'       => NodeResult::of($this->manager->clearTable($user, $this->requireTable($context))),
            'delete_table'      => NodeResult::of($this->manager->deleteTable($user, $this->requireTable($context))),
            'insert_row'        => $this->handleInsertRow($context, $user),
            'get_rows'          => $this->handleGetRows($context, $user),
            'update_rows'       => $this->handleUpdateRows($context, $user),
            'upsert_rows'       => $this->handleUpsertRows($context, $user),
            'delete_rows'       => $this->handleDeleteRows($context, $user),
            'if_row_exists'     => $this->handleExistenceGuard($context, $user, true),
            'if_row_not_exists' => $this->handleExistenceGuard($context, $user, false),
            default             => throw NodeExecutionException::because("عملیات Data table نامعتبر است: {$operation}"),
        };
    }

    private function handleRenameTable(NodeContext $context, $user): NodeResult
    {
        $table = $this->requireTable($context);
        $newName = trim((string) ($context->config['new_table_name'] ?? ''));

        $context->log("تغییر نام جدول «{$table}» به «{$newName}»");

        return NodeResult::of($this->manager->renameTable($user, $table, $newName));
    }

    private function handleInsertRow(NodeContext $context, $user): NodeResult
    {
        $table = $this->requireTable($context);
        $rows = $this->extractRowsPayload($context);

        $context->log("درج ردیف در جدول داده «{$table}»");

        return NodeResult::of($this->manager->insertRows($user, $table, $rows));
    }

    private function handleGetRows(NodeContext $context, $user): NodeResult
    {
        $table = $this->requireTable($context);
        $conditions = is_array($context->config['conditions'] ?? null) ? $context->config['conditions'] : [];
        $combinator = (string) ($context->config['combinator'] ?? 'and');
        $sortColumn = (string) ($context->config['sort_column'] ?? 'id');
        $sortDirection = (string) ($context->config['sort_direction'] ?? 'DESC');
        $limit = (int) ($context->config['limit'] ?? 100);

        $context->log("دریافت ردیف‌ها از جدول داده «{$table}»");

        return NodeResult::of(
            $this->manager->getRows($user, $table, $conditions, $combinator, $sortColumn, $sortDirection, $limit)
        );
    }

    private function handleUpdateRows(NodeContext $context, $user): NodeResult
    {
        $table = $this->requireTable($context);
        $rows = $this->extractRowsPayload($context);
        $valuesToSet = array_is_list($rows) ? ($rows[0] ?? []) : $rows;
        $conditions = is_array($context->config['conditions'] ?? null) ? $context->config['conditions'] : [];
        $combinator = (string) ($context->config['combinator'] ?? 'and');

        $context->log("بروزرسانی ردیف‌ها در جدول داده «{$table}»");

        return NodeResult::of(
            $this->manager->updateRows($user, $table, $valuesToSet, $conditions, $combinator)
        );
    }

    private function handleUpsertRows(NodeContext $context, $user): NodeResult
    {
        $table = $this->requireTable($context);
        $rows = $this->extractRowsPayload($context);
        $valuesToSet = array_is_list($rows) ? ($rows[0] ?? []) : $rows;
        $conditions = is_array($context->config['conditions'] ?? null) ? $context->config['conditions'] : [];
        $combinator = (string) ($context->config['combinator'] ?? 'and');

        $context->log("اجرای Upsert روی جدول داده «{$table}»");

        return NodeResult::of(
            $this->manager->upsertRows($user, $table, $valuesToSet, $conditions, $combinator)
        );
    }

    private function handleDeleteRows(NodeContext $context, $user): NodeResult
    {
        $table = $this->requireTable($context);
        $conditions = is_array($context->config['conditions'] ?? null) ? $context->config['conditions'] : [];
        $combinator = (string) ($context->config['combinator'] ?? 'and');

        $context->log("حذف ردیف‌ها از جدول داده «{$table}»");

        return NodeResult::of(
            $this->manager->deleteRows($user, $table, $conditions, $combinator)
        );
    }

    private function handleExistenceGuard(NodeContext $context, $user, bool $expectExists): NodeResult
    {
        $table = $this->requireTable($context);
        $conditions = is_array($context->config['conditions'] ?? null) ? $context->config['conditions'] : [];
        $combinator = (string) ($context->config['combinator'] ?? 'and');

        $check = $this->manager->checkRowExistence($user, $table, $conditions, $combinator);
        $passed = $expectExists ? $check['exists'] : ! $check['exists'];
        $handle = $passed ? HandleType::Main->value : HandleType::Stopped->value;

        $context->log(
            ($expectExists ? 'بررسی وجود ردیف' : 'بررسی عدم وجود ردیف')
            . " در جدول «{$table}» ← " . ($passed ? 'ادامه مسیر' : 'توقف مسیر')
        );

        return NodeResult::of(
            output: [
                'passed'        => $passed,
                'exists'        => $check['exists'],
                'matched_count' => $check['matched_count'],
                'row'           => $check['row'],
                'input'         => $context->input,
            ],
            handle: $handle,
            meta: ['taken_handle' => $handle],
        );
    }

    private function requireTable(NodeContext $context): string
    {
        $table = trim((string) (
            $context->config['table']
            ?? $context->config['table_name']
            ?? ''
        ));

        if ($table === '') {
            throw NodeExecutionException::because('نام جدول داده (Data Table) وارد نشده است.');
        }

        return $table;
    }

    private function extractRowsPayload(NodeContext $context): array
    {
        $mode = (string) ($context->config['data_mode'] ?? 'manual');

        if ($mode === 'json') {
            $raw = trim((string) ($context->config['json_data'] ?? ''));
            if ($raw === '') {
                throw NodeExecutionException::because('فیلد داده‌های JSON نمی‌تواند خالی باشد.');
            }

            $decoded = json_decode($raw, true);
            if (json_last_error() !== JSON_ERROR_NONE || ! is_array($decoded) || $decoded === []) {
                throw NodeExecutionException::because('ساختار JSON واردشده برای ردیف جدول معتبر نیست.');
            }

            return array_is_list($decoded) ? $decoded : [$decoded];
        }

        $fields = $context->config['row_fields'] ?? [];
        if (! is_array($fields) || $fields === []) {
            throw NodeExecutionException::because('حداقل یک ستون و مقدار باید در بخش «ستون‌ها و مقادیر» تعریف شود.');
        }

        $row = [];

        if (array_is_list($fields)) {
            foreach ($fields as $item) {
                if (! is_array($item)) {
                    continue;
                }
                $name = trim((string) ($item['name'] ?? ''));
                if ($name === '') {
                    continue;
                }
                $row[$name] = $this->castFieldValue($item['value'] ?? null, (string) ($item['type'] ?? 'string'));
            }
        } else {
            foreach ($fields as $key => $val) {
                $name = trim((string) $key);
                if ($name !== '') {
                    $row[$name] = $val;
                }
            }
        }

        if ($row === []) {
            throw NodeExecutionException::because('نام ستون‌ها نمی‌تواند خالی باشد.');
        }

        return [$row];
    }

    private function castFieldValue(mixed $value, string $type): mixed
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            'number'  => is_numeric($value) ? ($value + 0) : 0,
            'boolean' => is_bool($value)
                ? $value
                : in_array(strtolower(trim((string) $value)), ['true', '1', 'yes', 'on'], true),
            'array', 'object' => is_string($value) ? (json_decode($value, true) ?? $value) : $value,
            default   => (string) $value,
        };
    }
}
