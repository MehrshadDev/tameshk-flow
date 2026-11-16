<?php

namespace App\Workflow\Support;

use App\Models\Credential;
use App\Workflow\Exceptions\NodeExecutionException;
use PDO;
use PDOException;
use PDOStatement;
use Throwable;

final class MySqlClient
{
    private const MAX_ROWS = 1000;
    private const CONNECT_TIMEOUT = 10;

    private ?PDO $pdo = null;
    private string $host;
    private int $port;
    private string $database;
    private string $username;
    private string $password;
    private bool $useSsl;

    public function __construct(private readonly Credential $credential)
    {
        if ($this->credential->type !== 'mysql') {
            throw NodeExecutionException::because('نوع اعتبارنامه انتخابی برای MySQL معتبر نیست.');
        }

        $data = is_array($this->credential->data) ? $this->credential->data : [];

        $this->host = trim((string) ($data['host'] ?? ''));
        $this->port = (int) ($data['port'] ?? 3306);
        $this->database = trim((string) ($data['database'] ?? ''));
        $this->username = trim((string) ($data['username'] ?? ''));
        $this->password = (string) ($data['password'] ?? '');
        $this->useSsl = ((string) ($data['ssl'] ?? '0')) === '1';

        if ($this->host === '' || $this->database === '' || $this->username === '') {
            throw NodeExecutionException::because('اطلاعات اتصال در اعتبارنامه MySQL ناقص است.');
        }

        if ($this->port < 1 || $this->port > 65535) {
            throw NodeExecutionException::because('پورت MySQL نامعتبر است.');
        }

        $this->assertSafeDatabaseTarget();
    }

    public function listTables(int $limit = 300): array
    {
        $pdo = $this->connection();

        $sql = implode(' ', [
            'SELECT TABLE_NAME',
            'FROM INFORMATION_SCHEMA.TABLES',
            "WHERE TABLE_SCHEMA = :db AND TABLE_TYPE IN ('BASE TABLE', 'VIEW')",
            'ORDER BY TABLE_NAME ASC',
            'LIMIT :lim',
        ]);

        try {
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':db', $this->database, PDO::PARAM_STR);
            $stmt->bindValue(':lim', max(1, min(500, $limit)), PDO::PARAM_INT);
            $stmt->execute();

            $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $result = [];

            foreach ($rows as $tableName) {
                $name = (string) $tableName;
                if ($name !== '') {
                    $result[] = [
                        'value' => $name,
                        'label' => $name,
                    ];
                }
            }

            return $result;
        } catch (PDOException $e) {
            throw NodeExecutionException::because($this->describePdoError($e));
        }
    }

    public function selectRows(
        string $table,
        string $columnsRaw = '*',
        array $conditions = [],
        string $combinator = 'and',
        string $sortColumn = '',
        string $sortDirection = 'ASC',
        int $limit = 100,
        int $offset = 0,
    ): array {
        $quotedTable = $this->quoteTable($table);
        $selectClause = $this->buildSelectColumns($columnsRaw);
        [$whereSql, $bindings] = $this->buildWhereClause($conditions, $combinator);

        $parts = ['SELECT', $selectClause, 'FROM', $quotedTable];

        if ($whereSql !== '') {
            $parts[] = 'WHERE';
            $parts[] = $whereSql;
        }

        if (trim($sortColumn) !== '') {
            $quotedSort = $this->quoteIdentifier($sortColumn);
            $dir = strtoupper(trim($sortDirection)) === 'DESC' ? 'DESC' : 'ASC';
            $parts[] = "ORDER BY $quotedSort $dir";
        }

        $safeLimit = max(1, min(self::MAX_ROWS, $limit));
        $safeOffset = max(0, $offset);

        $parts[] = "LIMIT $safeLimit OFFSET $safeOffset";
        $sql = implode(' ', $parts);

        try {
            $stmt = $this->connection()->prepare($sql);
            $this->bindValues($stmt, $bindings);
            $stmt->execute();

            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'table' => $table,
                'count' => count($rows),
                'rows'  => $rows,
                'items' => $rows,
                'first' => $rows[0] ?? null,
            ];
        } catch (PDOException $e) {
            throw NodeExecutionException::because($this->describePdoError($e));
        }
    }

    public function insertRows(string $table, array $rows, bool $ignoreErrors = false): array
    {
        $normalizedRows = $this->normalizeRowList($rows);
        $quotedTable = $this->quoteTable($table);

        $columns = array_keys($normalizedRows[0]);
        if ($columns === []) {
            throw NodeExecutionException::because('هیچ ستونی برای درج (Insert) مشخص نشده است.');
        }

        $quotedColumns = array_map(fn (string $col) => $this->quoteIdentifier($col), $columns);
        $placeholders = '(' . implode(', ', array_fill(0, count($columns), '?')) . ')';
        $insertKeyword = $ignoreErrors ? 'INSERT IGNORE INTO' : 'INSERT INTO';

        $sql = implode(' ', [
            $insertKeyword,
            $quotedTable,
            '(' . implode(', ', $quotedColumns) . ')',
            'VALUES',
            $placeholders,
        ]);

        $pdo = $this->connection();
        $insertedCount = 0;
        $lastInsertId = null;

        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare($sql);

            foreach ($normalizedRows as $row) {
                $values = [];
                foreach ($columns as $col) {
                    $values[] = $this->normalizeScalarParam($row[$col] ?? null);
                }

                $this->bindValues($stmt, $values);
                $stmt->execute();
                $insertedCount += $stmt->rowCount();
                $lastInsertId = $pdo->lastInsertId() ?: $lastInsertId;
            }

            $pdo->commit();

            return [
                'success'        => true,
                'table'          => $table,
                'inserted_count' => $insertedCount,
                'last_insert_id' => is_numeric($lastInsertId) ? (int) $lastInsertId : $lastInsertId,
                'rows'           => $normalizedRows,
            ];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            if ($e instanceof PDOException) {
                throw NodeExecutionException::because($this->describePdoError($e));
            }

            throw $e;
        }
    }

    public function upsertRows(string $table, array $rows, string $updateColumnsRaw = ''): array
    {
        $normalizedRows = $this->normalizeRowList($rows);
        $quotedTable = $this->quoteTable($table);

        $columns = array_keys($normalizedRows[0]);
        if ($columns === []) {
            throw NodeExecutionException::because('هیچ ستونی برای عملیات Insert or Update مشخص نشده است.');
        }

        $quotedColumns = array_map(fn (string $col) => $this->quoteIdentifier($col), $columns);
        $placeholders = '(' . implode(', ', array_fill(0, count($columns), '?')) . ')';

        $updateCols = $this->resolveUpsertUpdateColumns($columns, $updateColumnsRaw);
        $updateAssignments = array_map(function (string $col) {
            $q = $this->quoteIdentifier($col);

            return "$q = VALUES($q)";
        }, $updateCols);

        $sql = implode(' ', [
            'INSERT INTO',
            $quotedTable,
            '(' . implode(', ', $quotedColumns) . ')',
            'VALUES',
            $placeholders,
            'ON DUPLICATE KEY UPDATE',
            implode(', ', $updateAssignments),
        ]);

        $pdo = $this->connection();
        $affectedCount = 0;
        $lastInsertId = null;

        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare($sql);

            foreach ($normalizedRows as $row) {
                $values = [];
                foreach ($columns as $col) {
                    $values[] = $this->normalizeScalarParam($row[$col] ?? null);
                }

                $this->bindValues($stmt, $values);
                $stmt->execute();
                $affectedCount += $stmt->rowCount();
                $lastInsertId = $pdo->lastInsertId() ?: $lastInsertId;
            }

            $pdo->commit();

            return [
                'success'        => true,
                'table'          => $table,
                'affected_rows'  => $affectedCount,
                'last_insert_id' => is_numeric($lastInsertId) ? (int) $lastInsertId : $lastInsertId,
                'rows'           => $normalizedRows,
            ];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            if ($e instanceof PDOException) {
                throw NodeExecutionException::because($this->describePdoError($e));
            }

            throw $e;
        }
    }

    public function updateRows(
        string $table,
        array $valuesToSet,
        array $conditions,
        string $combinator = 'and',
        ?int $limit = null,
    ): array {
        if ($valuesToSet === []) {
            throw NodeExecutionException::because('حداقل یک فیلد برای بروزرسانی (Update) باید تعیین شود.');
        }

        [$whereSql, $whereBindings] = $this->buildWhereClause($conditions, $combinator);

        if ($whereSql === '') {
            throw NodeExecutionException::because('برای جلوگیری از تغییر ناخواسته کل جدول، تعیین حداقل یک شرط (Where) در عملیات Update الزامی است.');
        }

        $quotedTable = $this->quoteTable($table);
        $setParts = [];
        $setBindings = [];

        foreach ($valuesToSet as $column => $val) {
            $quotedCol = $this->quoteIdentifier((string) $column);
            $setParts[] = "$quotedCol = ?";
            $setBindings[] = $this->normalizeScalarParam($val);
        }

        $parts = [
            'UPDATE',
            $quotedTable,
            'SET',
            implode(', ', $setParts),
            'WHERE',
            $whereSql,
        ];

        if ($limit !== null && $limit > 0) {
            $parts[] = 'LIMIT ' . min(100000, $limit);
        }

        $sql = implode(' ', $parts);

        try {
            $stmt = $this->connection()->prepare($sql);
            $this->bindValues($stmt, array_merge($setBindings, $whereBindings));
            $stmt->execute();

            return [
                'success'       => true,
                'table'         => $table,
                'affected_rows' => $stmt->rowCount(),
                'updated_data'  => $valuesToSet,
            ];
        } catch (PDOException $e) {
            throw NodeExecutionException::because($this->describePdoError($e));
        }
    }

    public function deleteTableOrRows(
        string $table,
        string $command = 'delete',
        array $conditions = [],
        string $combinator = 'and',
        ?int $limit = null,
    ): array {
        $quotedTable = $this->quoteTable($table);
        $cmd = strtolower(trim($command));

        try {
            if ($cmd === 'truncate') {
                $this->connection()->exec(implode(' ', ['TRUNCATE', 'TABLE', $quotedTable]));

                return [
                    'success'       => true,
                    'table'         => $table,
                    'command'       => 'truncate',
                    'affected_rows' => 0,
                ];
            }

            if ($cmd === 'drop') {
                $this->connection()->exec(implode(' ', ['DROP', 'TABLE', 'IF EXISTS', $quotedTable]));

                return [
                    'success'       => true,
                    'table'         => $table,
                    'command'       => 'drop',
                    'affected_rows' => 0,
                ];
            }

            [$whereSql, $whereBindings] = $this->buildWhereClause($conditions, $combinator);

            if ($whereSql === '') {
                throw NodeExecutionException::because('برای حذف ردیف‌ها (Delete Rows)، تعیین حداقل یک شرط الزامی است. اگر قصد خالی کردن کل جدول را دارید از دستور Truncate استفاده کنید.');
            }

            $parts = ['DELETE FROM', $quotedTable, 'WHERE', $whereSql];

            if ($limit !== null && $limit > 0) {
                $parts[] = 'LIMIT ' . min(100000, $limit);
            }

            $stmt = $this->connection()->prepare(implode(' ', $parts));
            $this->bindValues($stmt, $whereBindings);
            $stmt->execute();

            return [
                'success'       => true,
                'table'         => $table,
                'command'       => 'delete',
                'affected_rows' => $stmt->rowCount(),
            ];
        } catch (PDOException $e) {
            throw NodeExecutionException::because($this->describePdoError($e));
        }
    }

    public function executeQuery(string $rawQuery, array $parameters = []): array
    {
        $sql = trim($rawQuery);

        if ($sql === '') {
            throw NodeExecutionException::because('متن کوئری SQL نمی‌تواند خالی باشد.');
        }

        $this->assertSafeSqlStatement($sql);

        try {
            $pdo = $this->connection();
            $stmt = $pdo->prepare($sql);

            $normalizedParams = array_map(
                fn ($val) => $this->normalizeScalarParam($val),
                array_values($parameters)
            );

            $this->bindValues($stmt, $normalizedParams);
            $stmt->execute();

            if ($stmt->columnCount() > 0) {
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $truncated = false;

                if (count($rows) > self::MAX_ROWS) {
                    $rows = array_slice($rows, 0, self::MAX_ROWS);
                    $truncated = true;
                }

                return [
                    'success'   => true,
                    'count'     => count($rows),
                    'truncated' => $truncated,
                    'rows'      => $rows,
                    'items'     => $rows,
                    'first'     => $rows[0] ?? null,
                ];
            }

            $lastId = $pdo->lastInsertId();

            return [
                'success'        => true,
                'affected_rows'  => $stmt->rowCount(),
                'last_insert_id' => is_numeric($lastId) && $lastId !== '0' ? (int) $lastId : null,
            ];
        } catch (PDOException $e) {
            throw NodeExecutionException::because($this->describePdoError($e));
        }
    }

    public function quoteTable(string $table): string
    {
        $clean = trim($table);

        if ($clean === '') {
            throw NodeExecutionException::because('نام جدول (Table) الزامی است.');
        }

        $parts = explode('.', $clean);

        if (count($parts) > 2) {
            throw NodeExecutionException::because("نام جدول «{$table}» معتبر نیست.");
        }

        return implode('.', array_map(fn (string $part) => $this->quoteIdentifier($part), $parts));
    }

    public function quoteIdentifier(string $identifier): string
    {
        $clean = trim($identifier, " \t\n\r\0\x0B`");

        if ($clean === '' || mb_strlen($clean) > 64 || ! preg_match('/^[a-zA-Z0-9_]+$/', $clean)) {
            throw NodeExecutionException::because("نام فیلد یا جدول «{$identifier}» شامل کاراکترهای غیرمجاز است.");
        }

        return "`$clean`";
    }

    private function buildSelectColumns(string $columnsRaw): string
    {
        $trimmed = trim($columnsRaw);

        if ($trimmed === '' || $trimmed === '*') {
            return '*';
        }

        $parts = array_values(array_filter(array_map('trim', explode(',', $trimmed))));

        if ($parts === [] || in_array('*', $parts, true)) {
            return '*';
        }

        return implode(', ', array_map(fn (string $col) => $this->quoteIdentifier($col), $parts));
    }

    private function buildWhereClause(array $conditions, string $combinator): array
    {
        $clauses = [];
        $bindings = [];

        foreach ($conditions as $rule) {
            if (! is_array($rule)) {
                continue;
            }

            $rawField = trim((string) ($rule['field'] ?? ''));
            if ($rawField === '') {
                continue;
            }

            $column = $this->quoteIdentifier($rawField);
            $operator = (string) ($rule['operator'] ?? 'eq');
            $value = $rule['value'] ?? '';

            switch ($operator) {
                case 'exists':
                    $clauses[] = "$column IS NOT NULL";
                    break;

                case 'not_exists':
                    $clauses[] = "$column IS NULL";
                    break;

                case 'empty':
                    $clauses[] = "($column IS NULL OR $column = '')";
                    break;

                case 'not_empty':
                    $clauses[] = "($column IS NOT NULL AND $column != '')";
                    break;

                case 'is_true':
                    $clauses[] = "$column = 1";
                    break;

                case 'is_false':
                    $clauses[] = "($column = 0 OR $column IS NULL)";
                    break;

                case 'neq':
                    $clauses[] = "$column != ?";
                    $bindings[] = $this->normalizeScalarParam($value);
                    break;

                case 'gt':
                case 'date_after':
                    $clauses[] = "$column > ?";
                    $bindings[] = $this->normalizeScalarParam($value);
                    break;

                case 'gte':
                case 'date_after_or_eq':
                    $clauses[] = "$column >= ?";
                    $bindings[] = $this->normalizeScalarParam($value);
                    break;

                case 'lt':
                case 'date_before':
                    $clauses[] = "$column < ?";
                    $bindings[] = $this->normalizeScalarParam($value);
                    break;

                case 'lte':
                case 'date_before_or_eq':
                    $clauses[] = "$column <= ?";
                    $bindings[] = $this->normalizeScalarParam($value);
                    break;

                case 'contains':
                    $clauses[] = "$column LIKE ?";
                    $bindings[] = '%' . $this->escapeLike((string) $value) . '%';
                    break;

                case 'not_contains':
                    $clauses[] = "$column NOT LIKE ?";
                    $bindings[] = '%' . $this->escapeLike((string) $value) . '%';
                    break;

                case 'starts_with':
                    $clauses[] = "$column LIKE ?";
                    $bindings[] = $this->escapeLike((string) $value) . '%';
                    break;

                case 'not_starts_with':
                    $clauses[] = "$column NOT LIKE ?";
                    $bindings[] = $this->escapeLike((string) $value) . '%';
                    break;

                case 'ends_with':
                    $clauses[] = "$column LIKE ?";
                    $bindings[] = '%' . $this->escapeLike((string) $value);
                    break;

                case 'not_ends_with':
                    $clauses[] = "$column NOT LIKE ?";
                    $bindings[] = '%' . $this->escapeLike((string) $value);
                    break;

                case 'regex':
                    $clauses[] = "$column REGEXP ?";
                    $bindings[] = (string) $value;
                    break;

                case 'not_regex':
                    $clauses[] = "$column NOT REGEXP ?";
                    $bindings[] = (string) $value;
                    break;

                case 'eq':
                default:
                    $clauses[] = "$column = ?";
                    $bindings[] = $this->normalizeScalarParam($value);
                    break;
            }
        }

        if ($clauses === []) {
            return ['', []];
        }

        $glue = strtolower(trim($combinator)) === 'or' ? ' OR ' : ' AND ';

        return ['(' . implode($glue, $clauses) . ')', $bindings];
    }

    private function escapeLike(string $value): string
    {
        return addcslashes($value, '%_\\');
    }

    private function normalizeRowList(array $rows): array
    {
        if ($rows === []) {
            throw NodeExecutionException::because('داده‌ای برای ثبت در جدول ارسال نشده است.');
        }

        if (! array_is_list($rows)) {
            $rows = [$rows];
        }

        $first = $rows[0] ?? null;
        if (! is_array($first) || $first === [] || array_is_list($first)) {
            throw NodeExecutionException::because('ساختار داده‌های ردیف باید به صورت کلید-مقدار (نام ستون و مقدار) باشد.');
        }

        return $rows;
    }

    private function resolveUpsertUpdateColumns(array $allColumns, string $updateColumnsRaw): array
    {
        $trimmed = trim($updateColumnsRaw);

        if ($trimmed !== '') {
            $custom = array_values(array_filter(array_map('trim', explode(',', $trimmed))));
            if ($custom !== []) {
                return $custom;
            }
        }

        return $allColumns;
    }

    private function normalizeScalarParam(mixed $value): string|int|float|bool|null
    {
        if ($value === null || is_int($value) || is_float($value) || is_bool($value)) {
            return $value;
        }

        if (is_array($value) || is_object($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '';
        }

        return (string) $value;
    }

    private function bindValues(PDOStatement $stmt, array $bindings): void
    {
        foreach (array_values($bindings) as $index => $val) {
            $paramIndex = $index + 1;

            if ($val === null) {
                $stmt->bindValue($paramIndex, null, PDO::PARAM_NULL);
            } elseif (is_bool($val)) {
                $stmt->bindValue($paramIndex, $val ? 1 : 0, PDO::PARAM_INT);
            } elseif (is_int($val)) {
                $stmt->bindValue($paramIndex, $val, PDO::PARAM_INT);
            } else {
                $stmt->bindValue($paramIndex, (string) $val, PDO::PARAM_STR);
            }
        }
    }

    private function assertSafeSqlStatement(string $sql): void
    {
        $forbiddenPatterns = [
            '/\bLOAD\s+DATA\b/i',
            '/\bLOAD_FILE\s*\(/i',
            '/\bINTO\s+(OUTFILE|DUMPFILE)\b/i',
            '/\bSLEEP\s*\(/i',
            '/\bBENCHMARK\s*\(/i',
        ];

        foreach ($forbiddenPatterns as $pattern) {
            if (preg_match($pattern, $sql)) {
                throw NodeExecutionException::because('اجرای این دستور در کوئری MySQL به دلایل امنیتی مجاز نیست.');
            }
        }
    }

    private function assertSafeDatabaseTarget(): void
    {
        if (! config('workflow.http_block_private', true)) {
            return;
        }

        $appDbHost = strtolower(trim((string) config('database.connections.mysql.host', '')));
        $appDbName = strtolower(trim((string) config('database.connections.mysql.database', '')));

        if (
            $appDbName !== ''
            && strtolower($this->database) === $appDbName
            && in_array(strtolower($this->host), [$appDbHost, 'localhost', '127.0.0.1', '::1'], true)
        ) {
            throw NodeExecutionException::because('اتصال به پایگاه داده داخلی سیستم مجاز نیست.');
        }

        $syntheticUrl = 'http://' . $this->host;
        (new SafeHttpClient())->assertSafeUrl($syntheticUrl);
    }

    private function connection(): PDO
    {
        if ($this->pdo !== null) {
            return $this->pdo;
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            $this->host,
            $this->port,
            $this->database
        );

        $options = [
            PDO::ATTR_ERRMODE                  => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE       => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES         => false,
            PDO::ATTR_STRINGIFY_FETCHES        => false,
            PDO::ATTR_TIMEOUT                  => self::CONNECT_TIMEOUT,
            PDO::MYSQL_ATTR_LOCAL_INFILE       => false,
            PDO::MYSQL_ATTR_MULTI_STATEMENTS   => false,
        ];

        if ($this->useSsl) {
            $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
        }

        try {
            $this->pdo = new PDO($dsn, $this->username, $this->password, $options);

            return $this->pdo;
        } catch (PDOException $e) {
            throw NodeExecutionException::because($this->describePdoError($e, true));
        }
    }

    private function describePdoError(PDOException $e, bool $isConnect = false): string
    {
        $code = (int) ($e->errorInfo[1] ?? $e->getCode());
        $rawMessage = trim(strip_tags($e->getMessage()));

        if ($isConnect) {
            return match ($code) {
                1044, 1045 => 'خطای احراز هویت MySQL: نام کاربری یا رمز عبور پایگاه داده اشتباه است.',
                1049       => "پایگاه داده «{$this->database}» در سرور MySQL یافت نشد.",
                2002       => 'امکان برقراری ارتباط با سرور MySQL وجود ندارد. آدرس سرور، پورت و دسترسی فایروال (Remote MySQL) را بررسی کنید.',
                default    => 'خطا در اتصال به پایگاه داده MySQL: ' . mb_substr($rawMessage, 0, 300),
            };
        }

        return match ($code) {
            1146    => 'جدول مورد نظر در پایگاه داده یافت نشد.',
            1054    => 'یکی از ستون‌های واردشده در جدول وجود ندارد: ' . mb_substr($rawMessage, 0, 250),
            1062    => 'خطای مقدار تکراری (Duplicate Entry): رکوردی با این کلید یکتا از قبل وجود دارد.',
            1064    => 'خطای نگارشی در دستور SQL: ' . mb_substr($rawMessage, 0, 250),
            default => 'خطای پایگاه داده MySQL: ' . mb_substr($rawMessage, 0, 350),
        };
    }
}
