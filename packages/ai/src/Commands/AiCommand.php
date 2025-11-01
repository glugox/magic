<?php

namespace Glugox\Ai\Commands;

use Illuminate\Console\Command;

class AiCommand extends Command
{
    public $signature = 'ai';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
