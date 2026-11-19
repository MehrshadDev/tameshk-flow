<?php

use App\Providers\AppServiceProvider;
use App\Workflow\Providers\WorkflowServiceProvider;
use App\Mcp\Providers\McpServiceProvider;

return [
    AppServiceProvider::class,
    WorkflowServiceProvider::class,
    McpServiceProvider::class,
];
