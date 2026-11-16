<?php

namespace App\Workflow\Support;

use App\Models\DataTable;
use App\Models\DataTableRow;
use App\Models\User;
use App\Subscriptions\SubscriptionManager;
use App\Workflow\Exceptions\NodeExecutionException;
use Illuminate\Support\Facades\DB;

final class DataTableManager
{
    private const MAX_COLUMNS_PER_TABLE = 50;
    private const MAX_ROW_BYTES = 16384;

    public function __construct(
        private readonly SubscriptionManager $subscriptions,
    ) {}

    public function quotasFor(User $user): array
    {
        if (! $this->subscriptions->isSaasEnabled()) {
            return [
                'plan_name'          => 'نامحدود',
                'max_tables'         => 100,
                'max_rows_per_table' => 50000,
                'max_total_bytes'    => 250 * 1024 * 1024,
            ];
        }

        $plan = $this->subscriptions->resolvePlan($user);

        return match ($plan->slug) {
            'business' => [
                'plan_name'          => $plan->name,
                'max_tables'         => 100,
                'max_rows_per_table' => 50000,
                'max_total_bytes'    => 250 * 1024 * 1024,
            ],
            'pro' => [
                'plan_name'          => $plan->name,
                'max_tables'         => 25,
                'max_rows_per_table' => 10000,
                'max_total_bytes'    => 50 * 1024 * 1024,
            ],
            default => [
                'plan_name'          => $plan->name,
                'max_tables'         => 3,
                'max_rows_per_table' => 500,
                'max_total_bytes'    => 5 * 1024 * 1024,
            ],
        };
    }

    public function listTables(User $user): array
    {
        $tables = DataTable::query()
            ->where('user_id', $user->id)
            ->orderBy('name')
            ->get();

        $totalBytes = (int) $tables->sum('size_bytes');
        $quotas = $this->quotasFor($user);

        $items = $tables->map(fn (DataTable $t) => $this->formatTableSummary($t))->values()->all();

        return [
            'count'            => count($items),
            'tables'           => $items,
            'rows'             => $items,
            'total_size_bytes' => $totalBytes,
            'total_size_kb'    => round($totalBytes / 1024, 2),
            'max_tables'       => $quotas['max_tables'],
            'max_size_mb'      => round($quotas['max_total_bytes'] / (1024 * 1024), 1),
        ];
    }

    public function createTable(User $user, string $rawName, array $initialColumns = [], bool $ifNotExists = true): array
    {
        $name = $this->validateTableName($rawName);
        $cleanColumns = $this->sanitizeColumnNames($initialColumns);

        return DB::transaction(function () use ($user, $name, $cleanColumns, $ifNotExists) {
            User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();

            $existing = DataTable::query()
                ->where('user_id', $user->id)
                ->where('name', $name)
                ->first();

            if ($existing) {
                if ($ifNotExists) {
                    if ($cleanColumns !== []) {
                        $merged = array_values(array_unique(array_merge($existing->columns ?? [], $cleanColumns)));
                        if (count($merged) <= self::MAX_COLUMNS_PER_TABLE) {
                            $existing->update(['columns' => $merged]);
                        }
                    }

                    return [
                        'success' => true,
                        'created' => false,
                        'table'   => $this->formatTableSummary($existing),
                    ];
                }

                throw NodeExecutionException::because("جدولی با نام «{$name}» از قبل در حساب شما وجود دارد.");
            }

            $quotas = $this->quotasFor($user);
            $currentCount = DataTable::query()->where('user_id', $user->id)->count();

            if ($currentCount >= $quotas['max_tables']) {
                throw NodeExecutionException::because(
                    "سقف تعداد جداول در پلن «{$quotas['plan_name']}» ({$quotas['max_tables']} جدول) پر شده است. یکی از جداول قبلی را حذف کنید یا اشتراک خود را ارتقا دهید."
                );
            }

            $table = DataTable::create([
                'user_id'    => $user->id,
                'name'       => $name,
                'columns'    => $cleanColumns,
                'rows_count' => 0,
                'size_bytes' => 0,
            ]);

            return [
                'success' => true,
                'created' => true,
                'table'   => $this->formatTableSummary($table),
            ];
        });
    }

    public function renameTable(User $user, string $currentName, string $newRawName): array
    {
        $newName = $this->validateTableName($newRawName);

        return DB::transaction(function () use ($user, $currentName, $newName) {
            $table = $this->findTableOrFail($user, $currentName, true);

            if (mb_strtolower($table->name) !== mb_strtolower($newName)) {
                $exists = DataTable::query()
                    ->where('user_id', $user->id)
                    ->where('name', $newName)
                    ->whereKeyNot($table->id)
                    ->exists();

                if ($exists) {
                    throw NodeExecutionException::because("جدولی با نام «{$newName}» از قبل وجود دارد.");
                }

                $table->update(['name' => $newName]);
            }

            return [
                'success'  => true,
                'old_name' => $currentName,
                'new_name' => $table->name,
                'table'    => $this->formatTableSummary($table),
            ];
        });
    }

    public function clearTable(User $user, string $tableName): array
    {
        $cleanName = $this->validateTableName($tableName);

        return DB::transaction(function () use ($user, $cleanName) {
            $table = $this->findTable($user, $cleanName, true);

            if (! $table) {
                return [
                    'success'      => true,
                    'table'        => $cleanName,
                    'cleared_rows' => 0,
                ];
            }

            $deletedRows = $table->rows()->count();

            $table->rows()->delete();
            $table->update([
                'rows_count' => 0,
                'size_bytes' => 0,
            ]);

            return [
                'success'      => true,
                'table'        => $table->name,
                'cleared_rows' => $deletedRows,
            ];
        });
    }

    public function deleteTable(User $user, string $tableName): array
    {
        return DB::transaction(function () use ($user, $tableName) {
            $table = $this->findTableOrFail($user, $tableName, true);
            $name = $table->name;
            $deletedRows = $table->rows_count;

            $table->delete();

            return [
                'success'      => true,
                'deleted_name' => $name,
                'deleted_rows' => $deletedRows,
            ];
        });
    }

    public function insertRows(User $user, string $tableName, array $rawRows): array
    {
        $cleanName = $this->validateTableName($tableName);
        $rowsToInsert = $this->normalizeInputRows($rawRows);

        $this->createTable($user, $cleanName, [], true);

        return DB::transaction(function () use ($user, $cleanName, $rowsToInsert) {
            User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();

            $table = $this->findTableOrFail($user, $cleanName, true);
            $quotas = $this->quotasFor($user);

            $incomingCount = count($rowsToInsert);
            if (($table->rows_count + $incomingCount) > $quotas['max_rows_per_table']) {
                $formattedMax = number_format($quotas['max_rows_per_table']);
                throw NodeExecutionException::because(
                    "سقف تعداد ردیف‌های هر جدول در پلن «{$quotas['plan_name']}» ({$formattedMax} ردیف) به پایان رسیده است."
                );
            }

            $totalUserBytes = (int) DataTable::query()->where('user_id', $user->id)->sum('size_bytes');
            $knownColumns = $table->columns ?? [];
            $addedBytes = 0;
            $insertedRecords = [];

            foreach ($rowsToInsert as $row) {
                [$cleanRow, $rowBytes, $knownColumns] = $this->prepareRowPayload($row, $knownColumns);
                $addedBytes += $rowBytes;

                if (($totalUserBytes + $addedBytes) > $quotas['max_total_bytes']) {
                    $maxMb = round($quotas['max_total_bytes'] / (1024 * 1024), 1);
                    throw NodeExecutionException::because(
                        "فضای ذخیره‌سازی جداول شما در پلن «{$quotas['plan_name']}» ({$maxMb} مگابایت) پر شده است."
                    );
                }

                $created = $table->rows()->create([
                    'data'       => $cleanRow,
                    'size_bytes' => $rowBytes,
                ]);

                $insertedRecords[] = $created->toFormattedRecord();
            }

            $table->update([
                'columns'    => $knownColumns,
                'rows_count' => $table->rows_count + $incomingCount,
                'size_bytes' => $table->size_bytes + $addedBytes,
            ]);

            return [
                'success'        => true,
                'table'          => $table->name,
                'inserted_count' => count($insertedRecords),
                'row'            => $insertedRecords[0] ?? null,
                'rows'           => $insertedRecords,
                'items'          => $insertedRecords,
            ];
        });
    }

    public function getRows(
        User $user,
        string $tableName,
        array $conditions = [],
        string $combinator = 'and',
        string $sortColumn = 'id',
        string $sortDirection = 'DESC',
        int $limit = 100,
    ): array {
        $cleanName = $this->validateTableName($tableName);
        $table = $this->findTable($user, $cleanName);

        if (! $table) {
            return [
                'table'         => $cleanName,
                'total_matched' => 0,
                'count'         => 0,
                'rows'          => [],
                'items'         => [],
                'first'         => null,
            ];
        }

        $matched = $this->findMatchingModels($table, $conditions, $combinator);
        $formatted = array_map(fn (DataTableRow $r) => $r->toFormattedRecord(), $matched);

        $sortKey = trim($sortColumn) !== '' ? trim($sortColumn) : 'id';
        $isDesc = strtoupper(trim($sortDirection)) === 'DESC';

        usort($formatted, function (array $a, array $b) use ($sortKey, $isDesc) {
            $valA = $a[$sortKey] ?? null;
            $valB = $b[$sortKey] ?? null;

            if (is_numeric($valA) && is_numeric($valB)) {
                $cmp = (float) $valA <=> (float) $valB;
            } else {
                $cmp = strnatcasecmp((string) ($valA ?? ''), (string) ($valB ?? ''));
            }

            return $isDesc ? -$cmp : $cmp;
        });

        $safeLimit = max(1, min(1000, $limit));
        $sliced = array_slice($formatted, 0, $safeLimit);

        return [
            'table'         => $table->name,
            'total_matched' => count($formatted),
            'count'         => count($sliced),
            'rows'          => $sliced,
            'items'         => $sliced,
            'first'         => $sliced[0] ?? null,
        ];
    }

    public function checkRowExistence(
        User $user,
        string $tableName,
        array $conditions,
        string $combinator = 'and',
    ): array {
        $cleanName = $this->validateTableName($tableName);
        $table = $this->findTable($user, $cleanName);

        if (! $table) {
            return [
                'table'         => $cleanName,
                'exists'        => false,
                'matched_count' => 0,
                'row'           => null,
            ];
        }

        $matched = $this->findMatchingModels($table, $conditions, $combinator);
        $first = isset($matched[0]) ? $matched[0]->toFormattedRecord() : null;

        return [
            'table'         => $table->name,
            'exists'        => $matched !== [],
            'matched_count' => count($matched),
            'row'           => $first,
        ];
    }

    public function updateRows(
        User $user,
        string $tableName,
        array $valuesToSet,
        array $conditions,
        string $combinator = 'and',
    ): array {
        if ($valuesToSet === []) {
            throw NodeExecutionException::because('حداقل یک ستون و مقدار برای بروزرسانی ردیف‌ها الزامی است.');
        }

        if (! $this->hasValidCondition($conditions)) {
            throw NodeExecutionException::because('تعیین حداقل یک شرط (Filter Condition) برای بروزرسانی ردیف‌ها الزامی است.');
        }

        $cleanName = $this->validateTableName($tableName);

        return DB::transaction(function () use ($user, $cleanName, $valuesToSet, $conditions, $combinator) {
            User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();

            $table = $this->findTable($user, $cleanName, true);

            if (! $table) {
                return [
                    'success'       => true,
                    'table'         => $cleanName,
                    'updated_count' => 0,
                    'rows'          => [],
                    'items'         => [],
                    'first'         => null,
                ];
            }

            $quotas = $this->quotasFor($user);
            $totalUserBytes = (int) DataTable::query()->where('user_id', $user->id)->sum('size_bytes');

            $matchedModels = $this->findMatchingModels($table, $conditions, $combinator);
            $knownColumns = $table->columns ?? [];
            $netByteDiff = 0;
            $updatedRecords = [];

            foreach ($matchedModels as $rowModel) {
                $mergedData = array_merge($rowModel->data ?? [], $valuesToSet);
                unset($mergedData['id'], $mergedData['created_at'], $mergedData['updated_at']);

                [$cleanRow, $newBytes, $knownColumns] = $this->prepareRowPayload($mergedData, $knownColumns);
                $diff = $newBytes - $rowModel->size_bytes;
                $netByteDiff += $diff;

                if (($totalUserBytes + $netByteDiff) > $quotas['max_total_bytes']) {
                    $maxMb = round($quotas['max_total_bytes'] / (1024 * 1024), 1);
                    throw NodeExecutionException::because(
                        "فضای ذخیره‌سازی جداول شما در پلن «{$quotas['plan_name']}» ($maxMb مگابایت) پر شده است."
                    );
                }

                $rowModel->update([
                    'data'       => $cleanRow,
                    'size_bytes' => $newBytes,
                ]);

                $updatedRecords[] = $rowModel->fresh()->toFormattedRecord();
            }

            if ($updatedRecords !== []) {
                $table->update([
                    'columns'    => $knownColumns,
                    'size_bytes' => max(0, $table->size_bytes + $netByteDiff),
                ]);
            }

            return [
                'success'       => true,
                'table'         => $table->name,
                'updated_count' => count($updatedRecords),
                'rows'          => $updatedRecords,
                'items'         => $updatedRecords,
                'first'         => $updatedRecords[0] ?? null,
            ];
        });
    }

    public function upsertRows(
        User $user,
        string $tableName,
        array $valuesToSet,
        array $conditions,
        string $combinator = 'and',
    ): array {
        if ($valuesToSet === []) {
            throw NodeExecutionException::because('حداقل یک ستون و مقدار برای عملیات Upsert الزامی است.');
        }

        if (! $this->hasValidCondition($conditions)) {
            $inserted = $this->insertRows($user, $tableName, [$valuesToSet]);

            return [
                ...$inserted,
                'action' => 'inserted',
            ];
        }

        $existence = $this->checkRowExistence($user, $tableName, $conditions, $combinator);

        if ($existence['exists']) {
            $updated = $this->updateRows($user, $tableName, $valuesToSet, $conditions, $combinator);

            return [
                ...$updated,
                'action' => 'updated',
            ];
        }

        $inserted = $this->insertRows($user, $tableName, [$valuesToSet]);

        return [
            ...$inserted,
            'action' => 'inserted',
        ];
    }

    public function deleteRows(
        User $user,
        string $tableName,
        array $conditions,
        string $combinator = 'and',
    ): array {
        if (! $this->hasValidCondition($conditions)) {
            throw NodeExecutionException::because(
                'برای حذف ردیف‌ها، تعیین حداقل یک شرط الزامی است. اگر قصد خالی کردن کل جدول را دارید از عملیات «Clear a data table» استفاده کنید.'
            );
        }

        $cleanName = $this->validateTableName($tableName);

        return DB::transaction(function () use ($user, $cleanName, $conditions, $combinator) {
            $table = $this->findTable($user, $cleanName, true);

            if (! $table) {
                return [
                    'success'       => true,
                    'table'         => $cleanName,
                    'deleted_count' => 0,
                ];
            }

            $matchedModels = $this->findMatchingModels($table, $conditions, $combinator);

            if ($matchedModels === []) {
                return [
                    'success'       => true,
                    'table'         => $table->name,
                    'deleted_count' => 0,
                ];
            }

            $idsToDelete = [];
            $freedBytes = 0;

            foreach ($matchedModels as $rowModel) {
                $idsToDelete[] = $rowModel->id;
                $freedBytes += $rowModel->size_bytes;
            }

            DataTableRow::query()
                ->where('data_table_id', $table->id)
                ->whereIn('id', $idsToDelete)
                ->delete();

            $deletedCount = count($idsToDelete);

            $table->update([
                'rows_count' => max(0, $table->rows_count - $deletedCount),
                'size_bytes' => max(0, $table->size_bytes - $freedBytes),
            ]);

            return [
                'success'       => true,
                'table'         => $table->name,
                'deleted_count' => $deletedCount,
                'deleted_ids'   => $idsToDelete,
            ];
        });
    }

    private function findMatchingModels(DataTable $table, array $conditions, string $combinator): array
    {
        $hasCondition = $this->hasValidCondition($conditions);
        $matched = [];

        foreach ($table->rows()->orderBy('id')->cursor() as $rowModel) {
            if (! $hasCondition) {
                $matched[] = $rowModel;
                continue;
            }

            $record = $rowModel->toFormattedRecord();

            if (ConditionEvaluator::evaluateRules($conditions, $combinator, $record)) {
                $matched[] = $rowModel;
            }
        }

        return $matched;
    }

    private function hasValidCondition(array $conditions): bool
    {
        foreach ($conditions as $rule) {
            if (is_array($rule) && trim((string) ($rule['field'] ?? '')) !== '') {
                return true;
            }
        }

        return false;
    }

    private function prepareRowPayload(array $rawRow, array $knownColumns): array
    {
        $cleanRow = [];

        foreach ($rawRow as $key => $val) {
            $colName = $this->validateColumnName((string) $key);

            if (in_array($colName, ['id', 'created_at', 'updated_at'], true)) {
                continue;
            }

            $cleanRow[$colName] = $val;

            if (! in_array($colName, $knownColumns, true)) {
                $knownColumns[] = $colName;
            }
        }

        if ($cleanRow === []) {
            throw NodeExecutionException::because('اطلاعات ردیف خالی است. حداقل یک ستون معتبر وارد کنید.');
        }

        if (count($knownColumns) > self::MAX_COLUMNS_PER_TABLE) {
            throw NodeExecutionException::because(
                'تعداد ستون‌های هر جدول نمی‌تواند بیشتر از ' . self::MAX_COLUMNS_PER_TABLE . ' ستون باشد.'
            );
        }

        $encoded = json_encode($cleanRow, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($encoded === false) {
            throw NodeExecutionException::because('امکان ذخیره‌سازی داده‌های این ردیف وجود ندارد.');
        }

        $bytes = strlen($encoded);

        if ($bytes > self::MAX_ROW_BYTES) {
            throw NodeExecutionException::because('حجم داده‌های یک ردیف نمی‌تواند بیشتر از ۱۶ کیلوبایت باشد.');
        }

        return [$cleanRow, $bytes, $knownColumns];
    }

    private function normalizeInputRows(array $rawRows): array
    {
        if ($rawRows === []) {
            throw NodeExecutionException::because('داده‌ای برای درج در جدول ارسال نشده است.');
        }

        if (! array_is_list($rawRows)) {
            return [$rawRows];
        }

        $first = $rawRows[0] ?? null;
        if (! is_array($first) || $first === [] || array_is_list($first)) {
            throw NodeExecutionException::because('ساختار داده‌های ردیف باید به صورت کلید-مقدار (نام ستون و مقدار) باشد.');
        }

        return $rawRows;
    }

    private function findTable(User $user, string $rawName, bool $lock = false): ?DataTable
    {
        $name = trim($rawName);

        if ($name === '') {
            return null;
        }

        $query = DataTable::query()
            ->where('user_id', $user->id)
            ->where('name', $name);

        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->first();
    }

    private function findTableOrFail(User $user, string $rawName, bool $lock = false): DataTable
    {
        $name = trim($rawName);

        if ($name === '') {
            throw NodeExecutionException::because('نام جدول (Data table) وارد نشده است.');
        }

        $table = $this->findTable($user, $name, $lock);

        if (! $table) {
            throw NodeExecutionException::because("جدولی با نام «{$name}» یافت نشد.");
        }

        return $table;
    }

    private function validateTableName(string $rawName): string
    {
        $clean = trim(strip_tags($rawName));

        if ($clean === '' || mb_strlen($clean) > 64) {
            throw NodeExecutionException::because('نام جدول باید بین ۱ تا ۶۴ کاراکتر باشد.');
        }

        if (! preg_match('/^[\p{L}\p{N}_\- ]+$/u', $clean)) {
            throw NodeExecutionException::because('نام جدول فقط می‌تواند شامل حروف، اعداد، فاصله، خط تیره (-) و زیرخط (_) باشد.');
        }

        return $clean;
    }

    private function validateColumnName(string $rawCol): string
    {
        $clean = trim($rawCol);

        if ($clean === '' || mb_strlen($clean) > 64) {
            throw NodeExecutionException::because('نام ستون باید بین ۱ تا ۶۴ کاراکتر باشد.');
        }

        if (! preg_match('/^[\p{L}\p{N}_\- ]+$/u', $clean)) {
            throw NodeExecutionException::because("نام ستون «{$clean}» شامل کاراکترهای غیرمجاز است.");
        }

        return $clean;
    }

    private function sanitizeColumnNames(array $columns): array
    {
        $result = [];

        foreach ($columns as $col) {
            $trimmed = trim((string) $col);
            if ($trimmed === '' || in_array($trimmed, ['id', 'created_at', 'updated_at'], true)) {
                continue;
            }
            $validated = $this->validateColumnName($trimmed);
            if (! in_array($validated, $result, true)) {
                $result[] = $validated;
            }
        }

        if (count($result) > self::MAX_COLUMNS_PER_TABLE) {
            throw NodeExecutionException::because(
                'تعداد ستون‌های هر جدول نمی‌تواند بیشتر از ' . self::MAX_COLUMNS_PER_TABLE . ' ستون باشد.'
            );
        }

        return $result;
    }

    private function formatTableSummary(DataTable $table): array
    {
        return [
            'id'         => $table->public_id,
            'name'       => $table->name,
            'columns'    => $table->columns ?? [],
            'rows_count' => $table->rows_count,
            'size_bytes' => $table->size_bytes,
            'size_kb'    => round($table->size_bytes / 1024, 2),
            'created_at' => $table->created_at?->toIso8601String(),
            'updated_at' => $table->updated_at?->toIso8601String(),
        ];
    }
}
