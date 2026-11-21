<?php

namespace App\Templates;

use App\Mcp\Layout\WorkflowAutoLayout;
use App\Models\User;
use App\Models\Workflow;
use App\Templates\DTOs\WorkflowTemplate;
use App\Workflow\Graph\GraphSaver;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class TemplateInstaller
{
    public function __construct(
        private readonly WorkflowAutoLayout $autoLayout,
        private readonly GraphSaver $graphSaver,
    ) {}

    /**
     * @throws AuthorizationException
     */
    public function install(User $user, WorkflowTemplate $template): Workflow
    {
        return DB::transaction(function () use ($user, $template): Workflow {
            $lockedUser = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();

            $auth = Gate::forUser($lockedUser)->inspect('create', Workflow::class);

            if ($auth->denied()) {
                throw new AuthorizationException(
                    $auth->message() ?: 'به سقف تعداد ورکفلوهای مجاز در پلن فعلی رسیده‌اید.'
                );
            }

            $workflow = $lockedUser->workflows()->create([
                'name' => mb_substr($template->workflowName, 0, 100),
                'description' => mb_substr($template->workflowDescription, 0, 500),
                'is_active' => false,
                'published_at' => null,
                'settings' => [
                    'sort_order' => '000000',
                    'installed_from_template' => $template->slug,
                ],
            ]);

            $preparedNodes = array_map(fn (array $node) => [
                'key' => (string) $node['key'],
                'id' => null,
                'type' => (string) $node['type'],
                'name' => (string) $node['name'],
                'position' => $node['position'] ?? ['x' => 112.0, 'y' => 256.0],
                'config' => is_array($node['config'] ?? null) ? $node['config'] : [],
            ], $template->nodes);

            $preparedEdges = array_map(fn (array $edge) => [
                'source' => (string) $edge['source'],
                'target' => (string) $edge['target'],
                'sourceHandle' => (string) ($edge['sourceHandle'] ?? 'main'),
                'targetHandle' => $edge['targetHandle'] ?? 'main',
            ], $template->edges);

            $laidOutNodes = $this->autoLayout->layout($preparedNodes, $preparedEdges);

            $this->graphSaver->save($workflow, $laidOutNodes, $preparedEdges);

            return $workflow;
        });
    }
}
