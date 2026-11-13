<?php

namespace App\Backup;

use App\Jobs\BackupUserDataToGitJob;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

final class GitBackupObserver
{
    public const IMMEDIATE_PENDING_KEY = 'git_backup_immediate_pending';
    public const IMMEDIATE_DIRTY_KEY = 'git_backup_immediate_dirty';
    public const PERIODIC_DIRTY_KEY = 'git_backup_periodic_dirty';

    public function saved(Model $model): void
    {
        $this->handleModelEvent($model, false);
    }

    public function deleted(Model $model): void
    {
        $this->handleModelEvent($model, true);
    }

    private function handleModelEvent(Model $model, bool $isDeleted): void
    {
        if (! config('git_backup.enabled', false)) {
            return;
        }

        $table = $model->getTable();
        $tableConfig = $this->resolveTableConfig($table);

        if ($tableConfig === null) {
            return;
        }

        $mode = (string) ($tableConfig['mode'] ?? 'periodic');

        if (! $isDeleted && ! $this->matchesTriggerConditions($model, $tableConfig)) {
            return;
        }

        if (! $isDeleted && ! $model->wasRecentlyCreated) {
            $changedColumns = array_keys($model->getChanges());
            $ignoredColumns = array_unique(array_merge(
                ['updated_at'],
                (array) config('git_backup.global_excluded_columns', []),
                (array) ($tableConfig['excluded_columns'] ?? []),
                (array) ($tableConfig['ignored_trigger_columns'] ?? []),
            ));

            $meaningfulChanges = array_diff($changedColumns, $ignoredColumns);

            if ($meaningfulChanges === []) {
                $onlyExcludedChanged = array_diff(
                        $changedColumns,
                        array_merge(['updated_at'], (array) ($tableConfig['excluded_columns'] ?? []))
                    ) === [];

                if (! $onlyExcludedChanged) {
                    self::markPeriodicDirty();
                }

                return;
            }
        }

        if ($mode === 'immediate') {
            self::triggerImmediate();

            return;
        }

        self::markPeriodicDirty();
    }

    public static function triggerImmediate(): void
    {
        if (! config('git_backup.enabled', false)) {
            return;
        }

        Cache::put(self::IMMEDIATE_DIRTY_KEY, true, now()->addDays(7));

        $debounce = (int) (
            config('git_backup.immediate_debounce_seconds')
            ?? 15
        );

        if (Cache::add(self::IMMEDIATE_PENDING_KEY, true, now()->addMinutes(5))) {
            $job = BackupUserDataToGitJob::dispatch();

            if ($debounce > 0 && config('queue.default') !== 'sync') {
                $job->delay(now()->addSeconds($debounce));
            }
        }
    }

    public static function markPeriodicDirty(): void
    {
        if (! config('git_backup.enabled', false)) {
            return;
        }

        Cache::put(self::PERIODIC_DIRTY_KEY, true, now()->addDays(7));
    }

    private function matchesTriggerConditions(Model $model, array $tableConfig): bool
    {
        $conditions = $tableConfig['only_trigger_when'] ?? [];

        if (! is_array($conditions) || $conditions === []) {
            return true;
        }

        foreach ($conditions as $column => $expected) {
            $actual = $model->getAttribute($column);
            $allowed = is_array($expected) ? $expected : [$expected];

            if (! in_array($actual, $allowed, true)) {
                return false;
            }
        }

        return true;
    }

    private function resolveTableConfig(string $table): ?array
    {
        $tables = (array) config('git_backup.tables', []);

        if (isset($tables[$table]) && is_array($tables[$table])) {
            return $tables[$table];
        }

        if (in_array($table, $tables, true)) {
            return [
                'mode' => 'periodic',
                'primary_key' => 'id',
            ];
        }

        return null;
    }
}
