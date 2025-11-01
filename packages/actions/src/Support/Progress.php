<?php

namespace Glugox\Actions\Support;

use Glugox\Actions\Events\ActionProgressUpdated;
use Glugox\Actions\Models\ActionRun;

class Progress
{
    public function __construct(protected ActionRun $run) {}

    public function update(int $percent, ?string $message = null): void
    {
        $this->run->progress = max(0, min(100, $percent));
        if ($message !== null) {
            $this->run->message = $message;
        }
        $this->run->save();

        event(new ActionProgressUpdated($this->run));
    }
}
