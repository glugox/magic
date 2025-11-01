<?php

namespace Glugox\Actions;

use Glugox\Actions\Contracts\Action;
use Glugox\Actions\DTO\ActionContext;
use Glugox\Actions\Events\ActionProgressUpdated;
use Glugox\Actions\Jobs\ExecuteAction;
use Glugox\Actions\Models\ActionRun;
use Glugox\Actions\Support\Progress;
use Illuminate\Support\Facades\Bus;

class ActionRunner
{
    public function run(Action $action, array $params = [], array $targets = [], ?int $userId = null): ActionRun
    {
        $run = ActionRun::create([
            'action' => $action::class,
            'status' => 'running',
            'progress' => 0,
            'params' => $params,
            'targets' => $targets,
            'user_id' => $userId,
            'message' => 'Started',
        ]);

        event(new ActionProgressUpdated($run));

        $ctx = new ActionContext($params, $userId, $targets, $run->id);
        $progress = new Progress($run);

        $payload = $action->handle($ctx, $progress);

        $run->refresh();
        $run->status = 'done';
        $run->progress = 100;
        $run->message = $payload['message'] ?? 'Completed';
        $run->save();
        event(new ActionProgressUpdated($run));

        return $run;
    }

    public function dispatch(Action $action, array $params = [], array $targets = [], ?int $userId = null): ActionRun
    {
        $run = ActionRun::create([
            'action' => $action::class,
            'status' => 'pending',
            'progress' => 0,
            'params' => $params,
            'targets' => $targets,
            'user_id' => $userId,
            'message' => 'Queued',
        ]);

        Bus::dispatch(new ExecuteAction($action::class, $run->id));

        event(new ActionProgressUpdated($run));
        return $run;
    }
}
