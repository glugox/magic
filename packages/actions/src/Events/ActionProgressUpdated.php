<?php

namespace Glugox\Actions\Events;

use Glugox\Actions\Models\ActionRun;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class ActionProgressUpdated implements ShouldBroadcast
{
    public function __construct(public ActionRun $run) {}

    public function broadcastOn()
    {
        $name = config('actions.broadcast_channel', 'private-action-run.{id}');
        $channel = str_replace('{id}', (string)$this->run->id, $name);
        return new PrivateChannel($channel);
    }

    public function broadcastAs()
    {
        return 'ActionProgressUpdated';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->run->id,
            'status' => $this->run->status,
            'progress' => $this->run->progress,
            'message' => $this->run->message,
            'action' => $this->run->action,
            'updated_at' => $this->run->updated_at?->toISOString(),
        ];
    }
}
