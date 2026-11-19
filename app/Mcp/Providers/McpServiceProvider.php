<?php

namespace App\Mcp\Providers;

use App\Mcp\Layout\WorkflowAutoLayout;
use App\Mcp\OAuth\McpOAuthService;
use App\Mcp\Registry\McpToolRegistry;
use App\Mcp\Server\McpServer;
use App\Mcp\Support\WorkflowGraphBuilder;
use App\Mcp\Support\WorkflowLookup;
use App\Mcp\Tools\CreateWorkflowTool;
use App\Mcp\Tools\DeleteWorkflowTool;
use App\Mcp\Tools\GetNodeSchemaTool;
use App\Mcp\Tools\GetWorkflowRunsTool;
use App\Mcp\Tools\GetWorkflowTool;
use App\Mcp\Tools\LayoutWorkflowTool;
use App\Mcp\Tools\ListCredentialModelsTool;
use App\Mcp\Tools\ListNodeCatalogTool;
use App\Mcp\Tools\ListUserCredentialsTool;
use App\Mcp\Tools\ListWorkflowsTool;
use App\Mcp\Tools\ManageWorkflowStateTool;
use App\Mcp\Tools\RunWorkflowTool;
use App\Mcp\Tools\SaveWorkflowGraphTool;
use Illuminate\Support\ServiceProvider;

class McpServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(McpOAuthService::class);
        $this->app->singleton(WorkflowAutoLayout::class);
        $this->app->singleton(WorkflowGraphBuilder::class);
        $this->app->singleton(WorkflowLookup::class);

        $this->app->singleton(McpToolRegistry::class, function () {
            return new McpToolRegistry([
                ListNodeCatalogTool::class,
                GetNodeSchemaTool::class,
                ListUserCredentialsTool::class,
                ListCredentialModelsTool::class,
                ListWorkflowsTool::class,
                GetWorkflowTool::class,
                CreateWorkflowTool::class,
                SaveWorkflowGraphTool::class,
                LayoutWorkflowTool::class,
                ManageWorkflowStateTool::class,
                DeleteWorkflowTool::class,
                RunWorkflowTool::class,
                GetWorkflowRunsTool::class,
            ]);
        });

        $this->app->singleton(McpServer::class);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(base_path('routes/mcp.php'));
    }
}
