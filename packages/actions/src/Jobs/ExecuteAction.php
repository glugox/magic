<?php

namespace Glugox\Actions\Jobs;

use Glugox\Actions\Contracts\Action;
use Glugox\Actions\DTO\ActionContext;
use Glugox\Actions\Events\ActionProgressUpdated;
use Glugox\Actions\Models\ActionRun;
use Glugox\Actions\Support\Progress;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Throwable;

class ExecuteAction implements ShouldQueue
{
    use Dispatchable, Queueable;

    public function __construct(
        protected string $actionClass,
        protected int $runId
    ) {}

    public function handle(): void
    {
        $run = ActionRun::findOrFail($this->runId);
        $run->status = 'running';
        $run->save();
        event(new ActionProgressUpdated($run));

        /** @var Action $action */
        $action = app($this->actionClass);

        $ctx = new ActionContext(
            params: $run->params ?? [],
            userId: $run->user_id,
            targets: $run->targets ?? [],
            runId: $run->id,
        );
        $progress = new Progress($run);

        try {
            $payload = $action->handle($ctx, $progress);
            $run->status = 'done';
            $run->progress = 100;
            $run->message = $payload['message'] ?? 'Completed';
            $run->save();
            event(new ActionProgressUpdated($run));
        } catch (Throwable $e) {
            $run->status = 'failed';
            $run->message = $e->getMessage();
            $run->save();
            event(new ActionProgressUpdated($run));
            throw $e;
        }
    }
}
