<?php

namespace App\Workflow\Contracts;

use App\Models\Credential;
use App\Models\Node;
use App\Models\Workflow;

interface PollingTriggerContract extends PublishableTriggerContract
{
    public function poll(Workflow $workflow, Node $trigger, ?Credential $credential): array;
}
