<?php

namespace App\Workflow\Nodes\Actions;

use App\Workflow\Contracts\NodeContract;
use App\Workflow\DTOs\NodeContext;
use App\Workflow\DTOs\NodeDefinition;
use App\Workflow\DTOs\NodeResult;
use App\Workflow\Enums\NodeCategory;
use App\Workflow\Exceptions\NodeExecutionException;
use App\Workflow\Support\MySqlClient;

class MySqlNode implements NodeContract
{
    public static function definition(): NodeDefinition
    {
        return new NodeDefinition(
            type: 'action.mysql',
            name: 'MySQL',
            category: NodeCategory::Action,
            description: 'اجرای کوئری SQL و انتخاب، درج، ویرایش یا حذف ردیف‌ها در پایگاه داده MySQL.',
            icon: 'brand:mysql',
            group: 'mysql',
            inputs: ['main'],
            outputs: ['main'],
            configSchema: [
                'credential_id' => [
                    'type'            => 'credential',
                    'credential_type' => 'mysql',
                    'label'           => 'اعتبارنامه MySQL',
                    'required'        => true,
                ],
                'operation' => [
                    'type'     => 'select',
                    'label'    => 'عملیات (Operation)',
                    'options'  => [
                        ['value' => 'delete', 'label' => 'حذف جدول یا ردیف‌ها (Delete table or rows)'],
                        ['value' => 'execute_query', 'label' => 'اجرای کوئری SQL (Execute a SQL query)'],
                        ['value' => 'insert', 'label' => 'درج ردیف در جدول (Insert rows in a table)'],
                        ['value' => 'upsert', 'label' => 'درج یا بروزرسانی ردیف‌ها (Insert or update rows in a table)'],
                        ['value' => 'select', 'label' => 'انتخاب ردیف‌ها از جدول (Select rows from a table)'],
                        ['value' => 'update', 'label' => 'بروزرسانی ردیف‌ها در جدول (Update rows in a table)'],
                    ],
                    'default'  => 'select',
                    'required' => true,
                ],

                'table' => [
                    'type'        => 'dynamic_select',
                    'source'      => 'mysql_tables',
                    'label'       => 'نام جدول (Table)',
                    'required'    => true,
                    'placeholder' => 'users',
                    'depends_on'  => [
                        'operation' => ['select', 'insert', 'update', 'upsert', 'delete'],
                    ],
                ],

                'delete_command' => [
                    'type'       => 'select',
                    'label'      => 'نوع دستور حذف (Command)',
                    'options'    => [
                        ['value' => 'delete', 'label' => 'حذف ردیف‌های منطبق با شرط (Delete Rows)'],
                        ['value' => 'truncate', 'label' => 'خالی کردن کامل جدول (Truncate Table)'],
                        ['value' => 'drop', 'label' => 'حذف کامل جدول از دیتابیس (Drop Table)'],
                    ],
                    'default'    => 'delete',
                    'required'   => true,
                    'depends_on' => ['operation' => 'delete'],
                ],

                'query' => [
                    'type'        => 'code',
                    'label'       => 'کوئری SQL (SQL Query)',
                    'required'    => true,
                    'default'     => "SELECT * FROM users WHERE id = ? LIMIT 10;",
                    'placeholder' => "SELECT * FROM orders WHERE status = ? LIMIT 50;",
                    'help'        => 'برای امنیت کامل در برابر SQL Injection، به جای قرار دادن مستقیم متغیرها در متن کوئری، از علامت ? استفاده کنید و مقادیر را در فیلد پارامترها وارد نمایید.',
                    'depends_on'  => ['operation' => 'execute_query'],
                ],
                'query_params' => [
                    'type'        => 'textarea',
                    'label'       => 'پارامترهای کوئری (Query Parameters - اختیاری)',
                    'placeholder' => '["paid", 100] یا {{ input.id }}',
                    'help'        => 'آرایه JSON یا مقادیر جداشده با کاما که به ترتیب جایگزین علامت‌های ? در کوئری می‌شوند.',
                    'depends_on'  => ['operation' => 'execute_query'],
                ],

                'data_mode' => [
                    'type'       => 'select',
                    'label'      => 'نحوه ورود داده‌های ستون‌ها (Data Mode)',
                    'options'    => [
                        ['value' => 'manual', 'label' => 'نگاشت دستی ستون‌ها (Define Below)'],
                        ['value' => 'json', 'label' => 'آرایه یا آبجکت JSON'],
                    ],
                    'default'    => 'manual',
                    'required'   => true,
                    'depends_on' => [
                        'operation' => ['insert', 'update', 'upsert'],
                    ],
                ],
                'columns_data' => [
                    'type'       => 'set_fields',
                    'label'      => 'ستون‌ها و مقادیر (Columns & Values)',
                    'default'    => [],
                    'help'       => 'نام هر ستون جدول و مقدار متناظر آن را وارد کنید (می‌توانید فیلدهای ورودی را به اینجا بکشید).',
                    'depends_on' => [
                        'operation' => ['insert', 'update', 'upsert'],
                        'data_mode' => 'manual',
                    ],
                ],
                'json_data' => [
                    'type'        => 'code',
                    'label'       => 'داده‌های JSON (JSON Object / Rows)',
                    'default'     => "{\n  \"name\": \"Ali\",\n  \"email\": \"ali@example.com\"\n}",
                    'placeholder' => "{\n  \"name\": \"Ali\",\n  \"email\": \"ali@example.com\"\n}",
                    'depends_on'  => [
                        'operation' => ['insert', 'update', 'upsert'],
                        'data_mode' => 'json',
                    ],
                ],
                'update_columns' => [
                    'type'        => 'text',
                    'label'       => 'ستون‌های قابل بروزرسانی در صورت تکراری بودن (اختیاری)',
                    'placeholder' => 'name, email, status (خالی = همه ستون‌های ارسالی)',
                    'help'        => 'در صورت برخورد با کلید یکتای تکراری (ON DUPLICATE KEY)، کدام ستون‌ها آپدیت شوند؟ با کاما جدا کنید.',
                    'depends_on'  => ['operation' => 'upsert'],
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
                        'operation' => ['select', 'update', 'delete'],
                    ],
                ],
                'conditions' => [
                    'type'       => 'conditions',
                    'label'      => 'شروط فیلتر ردیف‌ها (Where Conditions)',
                    'default'    => [],
                    'help'       => 'در «مقدار اول» نام ستون جدول (مثلاً id یا email) و در «مقدار دوم» مقدار مورد نظر را وارد کنید.',
                    'depends_on' => [
                        'operation' => ['select', 'update', 'delete'],
                    ],
                ],

                'select_columns' => [
                    'type'        => 'text',
                    'label'       => 'ستون‌های خروجی (Output Columns)',
                    'default'     => '*',
                    'placeholder' => 'id, name, email, created_at',
                    'advanced'    => true,
                    'depends_on'  => ['operation' => 'select'],
                ],
                'sort_column' => [
                    'type'        => 'text',
                    'label'       => 'مرتب‌سازی بر اساس ستون (Order By)',
                    'placeholder' => 'id',
                    'advanced'    => true,
                    'depends_on'  => ['operation' => 'select'],
                ],
                'sort_direction' => [
                    'type'       => 'select',
                    'label'      => 'جهت مرتب‌سازی (Sort Direction)',
                    'options'    => [
                        ['value' => 'ASC', 'label' => 'صعودی (ASC)'],
                        ['value' => 'DESC', 'label' => 'نزولی (DESC)'],
                    ],
                    'default'    => 'DESC',
                    'advanced'   => true,
                    'depends_on' => ['operation' => 'select'],
                ],
                'limit' => [
                    'type'       => 'number',
                    'label'      => 'حداکثر تعداد ردیف (Limit)',
                    'default'    => 100,
                    'min'        => 1,
                    'max'        => 1000,
                    'integer'    => true,
                    'advanced'   => true,
                    'depends_on' => [
                        'operation' => ['select', 'update', 'delete'],
                    ],
                ],
                'offset' => [
                    'type'       => 'number',
                    'label'      => 'پرش از ابتدای نتایج (Offset)',
                    'default'    => 0,
                    'min'        => 0,
                    'max'        => 1000000,
                    'integer'    => true,
                    'advanced'   => true,
                    'depends_on' => ['operation' => 'select'],
                ],
                'ignore_errors' => [
                    'type'       => 'boolean',
                    'label'      => 'نادیده گرفتن خطاهای تکراری (INSERT IGNORE)',
                    'default'    => false,
                    'advanced'   => true,
                    'depends_on' => ['operation' => 'insert'],
                ],
            ],
            presets: [
                [
                    'key'         => 'delete_rows',
                    'name'        => 'Delete table or rows',
                    'description' => 'حذف ردیف‌های منطبق با شرط، خالی کردن (Truncate) یا حذف کامل جدول (Drop).',
                    'config'      => ['operation' => 'delete', 'delete_command' => 'delete'],
                ],
                [
                    'key'         => 'execute_query',
                    'name'        => 'Execute a SQL query',
                    'description' => 'اجرای مستقیم کوئری SQL پارامتریک روی پایگاه داده MySQL.',
                    'config'      => ['operation' => 'execute_query'],
                ],
                [
                    'key'         => 'insert_rows',
                    'name'        => 'Insert rows in a table',
                    'description' => 'درج یک یا چند ردیف جدید در جدول پایگاه داده MySQL.',
                    'config'      => ['operation' => 'insert'],
                ],
                [
                    'key'         => 'upsert_rows',
                    'name'        => 'Insert or update rows in a table',
                    'description' => 'درج ردیف جدید یا بروزرسانی خودکار آن در صورت تکراری بودن کلید یکتا (Upsert).',
                    'config'      => ['operation' => 'upsert'],
                ],
                [
                    'key'         => 'select_rows',
                    'name'        => 'Select rows from a table',
                    'description' => 'جستجو، فیلتر و دریافت ردیف‌ها از جدول پایگاه داده MySQL.',
                    'config'      => ['operation' => 'select'],
                ],
                [
                    'key'         => 'update_rows',
                    'name'        => 'Update rows in a table',
                    'description' => 'بروزرسانی مقادیر ستون‌ها برای ردیف‌های منطبق با شرط در جدول.',
                    'config'      => ['operation' => 'update'],
                ],
            ],
            color: 'sky',
        );
    }

    public function execute(NodeContext $context): NodeResult
    {
        $credential = $context->credential();

        if (! $credential) {
            throw NodeExecutionException::because('اعتبارنامه MySQL انتخاب نشده است.');
        }

        $client = new MySqlClient($credential);
        $operation = (string) ($context->config['operation'] ?? 'select');

        return match ($operation) {
            'select'        => $this->handleSelect($context, $client),
            'insert'        => $this->handleInsert($context, $client),
            'update'        => $this->handleUpdate($context, $client),
            'upsert'        => $this->handleUpsert($context, $client),
            'delete'        => $this->handleDelete($context, $client),
            'execute_query' => $this->handleExecuteQuery($context, $client),
            default         => throw NodeExecutionException::because("عملیات MySQL نامعتبر است: $operation"),
        };
    }

    private function handleSelect(NodeContext $context, MySqlClient $client): NodeResult
    {
        $table = $this->requireTable($context);
        $columns = (string) ($context->config['select_columns'] ?? '*');
        $conditions = is_array($context->config['conditions'] ?? null) ? $context->config['conditions'] : [];
        $combinator = (string) ($context->config['combinator'] ?? 'and');
        $sortColumn = (string) ($context->config['sort_column'] ?? '');
        $sortDirection = (string) ($context->config['sort_direction'] ?? 'DESC');
        $limit = (int) ($context->config['limit'] ?? 100);
        $offset = (int) ($context->config['offset'] ?? 0);

        $context->log("اجرای SELECT روی جدول $table");

        return NodeResult::of(
            $client->selectRows($table, $columns, $conditions, $combinator, $sortColumn, $sortDirection, $limit, $offset)
        );
    }

    private function handleInsert(NodeContext $context, MySqlClient $client): NodeResult
    {
        $table = $this->requireTable($context);
        $rows = $this->extractRowsPayload($context);
        $ignoreErrors = filter_var($context->config['ignore_errors'] ?? false, FILTER_VALIDATE_BOOLEAN);

        $context->log("درج ردیف در جدول $table");

        return NodeResult::of($client->insertRows($table, $rows, $ignoreErrors));
    }

    private function handleUpsert(NodeContext $context, MySqlClient $client): NodeResult
    {
        $table = $this->requireTable($context);
        $rows = $this->extractRowsPayload($context);
        $updateColumns = (string) ($context->config['update_columns'] ?? '');

        $context->log("اجرای Insert or Update روی جدول $table");

        return NodeResult::of($client->upsertRows($table, $rows, $updateColumns));
    }

    private function handleUpdate(NodeContext $context, MySqlClient $client): NodeResult
    {
        $table = $this->requireTable($context);
        $rows = $this->extractRowsPayload($context);
        $valuesToSet = array_is_list($rows) ? ($rows[0] ?? []) : $rows;

        if (! is_array($valuesToSet) || $valuesToSet === []) {
            throw NodeExecutionException::because('ستون‌ها و مقادیر جهت بروزرسانی (Update) مشخص نشده‌اند.');
        }

        $conditions = is_array($context->config['conditions'] ?? null) ? $context->config['conditions'] : [];
        $combinator = (string) ($context->config['combinator'] ?? 'and');
        $limit = isset($context->config['limit']) && is_numeric($context->config['limit'])
            ? (int) $context->config['limit']
            : null;

        $context->log("بروزرسانی ردیف‌ها در جدول $table");

        return NodeResult::of($client->updateRows($table, $valuesToSet, $conditions, $combinator, $limit));
    }

    private function handleDelete(NodeContext $context, MySqlClient $client): NodeResult
    {
        $table = $this->requireTable($context);
        $command = (string) ($context->config['delete_command'] ?? 'delete');
        $conditions = is_array($context->config['conditions'] ?? null) ? $context->config['conditions'] : [];
        $combinator = (string) ($context->config['combinator'] ?? 'and');
        $limit = isset($context->config['limit']) && is_numeric($context->config['limit'])
            ? (int) $context->config['limit']
            : null;

        $context->log("اجرای عملیات حذف ($command) روی جدول $table");

        return NodeResult::of($client->deleteTableOrRows($table, $command, $conditions, $combinator, $limit));
    }

    private function handleExecuteQuery(NodeContext $context, MySqlClient $client): NodeResult
    {
        $query = trim((string) ($context->config['query'] ?? ''));
        $params = $this->parseQueryParameters((string) ($context->config['query_params'] ?? ''));

        $context->log('اجرای کوئری سفارشی MySQL');

        return NodeResult::of($client->executeQuery($query, $params));
    }

    private function requireTable(NodeContext $context): string
    {
        $table = trim((string) ($context->config['table'] ?? ''));

        if ($table === '') {
            throw NodeExecutionException::because('نام جدول (Table) الزامی است.');
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
                throw NodeExecutionException::because('ساختار JSON واردشده برای ستون‌ها معتبر نیست.');
            }

            return array_is_list($decoded) ? $decoded : [$decoded];
        }

        $fields = $context->config['columns_data'] ?? [];
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
                ? ($value ? 1 : 0)
                : (in_array(strtolower(trim((string) $value)), ['true', '1', 'yes', 'on'], true) ? 1 : 0),
            'array', 'object' => is_string($value) ? $value : json_encode($value, JSON_UNESCAPED_UNICODE),
            default   => (string) $value,
        };
    }

    private function parseQueryParameters(string $raw): array
    {
        $trimmed = trim($raw);

        if ($trimmed === '') {
            return [];
        }

        if (str_starts_with($trimmed, '[')) {
            $decoded = json_decode($trimmed, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return array_values($decoded);
            }
        }

        return array_map('trim', explode(',', $trimmed));
    }
}
