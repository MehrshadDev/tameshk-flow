<?php

namespace App\Jobs;

use App\Models\Workflow;
use App\Workflow\Runner\WorkflowRunner;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Throwable;

class RunWorkflowJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    public int $tries = 1;

    public int $timeout = 180;

    public readonly string $executionKey;

    public function __construct(
        public readonly int $workflowId,
        public readonly array $triggerData,
        ?string $executionKey = null,
    ) {
        $this->executionKey = $executionKey ?: (string) Str::uuid();
    }

    public static function dispatchSmart(int $workflowId, array $triggerData): void
    {
        self::dispatchAfterResponse($workflowId, $triggerData);
    }

    public function handle(WorkflowRunner $runner): void
    {
        ignore_user_abort(true);
        @set_time_limit(180);

        if (! Cache::add("wf_job_exec_$this->executionKey", true, now()->addMinutes(15))) {
            return;
        }

        $workflow = Workflow::query()->find($this->workflowId);

        if (! $workflow || ! $workflow->isPublished() || ! $workflow->is_active) {
            return;
        }

        try {
            $runner->run($workflow, $this->triggerData);
        } catch (Throwable $e) {
            report($e);
        }
    }
}
