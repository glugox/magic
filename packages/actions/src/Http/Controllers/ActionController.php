<?php

namespace Glugox\Actions\Http\Controllers;

use Glugox\Actions\ActionResolver;
use Glugox\Actions\ActionRunner;
use Glugox\Actions\Contracts\Action;
use Glugox\Actions\Models\ActionRun;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class ActionController extends Controller
{
    use AuthorizesRequests;

    public function run(Request $request, ActionRunner $runner)
    {
        $data = $request->validate([
            'action' => 'required|string',
            'queued' => 'sometimes|boolean',
            'params' => 'array',
            'targets' => 'array',
        ]);

        $actionAlias = $data['action'];
        $params = $data['params'] ?? [];
        $targets = $data['targets'] ?? [];
        $queued = (bool)($data['queued'] ?? false);

        $actionClass = ActionResolver::resolve($actionAlias);
        $this->authorizeActionClass($actionClass);

        /** @var Action $action */
        $action = app($actionClass);

        $userId = $request->user()?->getAuthIdentifier();
        $run = $queued
            ? $runner->dispatch($action, $params, $targets, $userId)
            : $runner->run($action, $params, $targets, $userId);

        return response()->json(['run_id' => $run->id, 'status' => $run->status]);
    }

    protected function authorizeActionClass(string $actionClass): void
    {
        if (! is_subclass_of($actionClass, Action::class) && ! in_array(Action::class, class_implements($actionClass) ?: [])) {
            abort(422, 'Invalid action class.');
        }

        $allowed = config('actions.allowed', []);
        if (!empty($allowed) && ! in_array($actionClass, $allowed)) {
            abort(403, 'Action not allowed.');
        }
    }

    public function show(ActionRun $run)
    {
        return response()->json([
            'id' => $run->id,
            'action' => $run->action,
            'status' => $run->status,
            'progress' => $run->progress,
            'message' => $run->message,
            'params' => $run->params,
            'targets' => $run->targets,
            'user_id' => $run->user_id,
            'created_at' => $run->created_at,
            'updated_at' => $run->updated_at,
        ]);
    }
}
