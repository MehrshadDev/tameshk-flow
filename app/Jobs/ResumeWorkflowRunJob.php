<?php

namespace App\Jobs;

use App\Workflow\Runner\WorkflowRunner;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class ResumeWorkflowRunJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    public int $tries = 1;

    public int $timeout = 600;

    public function __construct(
        public readonly int $workflowRunId,
    ) {}

    public function handle(WorkflowRunner $runner): void
    {
        ignore_user_abort(true);
        @set_time_limit(300);

        try {
            $runner->resume($this->workflowRunId);
        } catch (Throwable $e) {
            report($e);
        }
    }
}
