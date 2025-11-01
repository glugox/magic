<?php

namespace App\Actions;

use Glugox\Actions\Contracts\Action;
use Glugox\Actions\DTO\ActionContext;
use Glugox\Actions\Support\Progress;

class ExportUsers implements Action
{
    public function name(): string { return 'Export Users'; }

    public function handle(ActionContext $ctx, Progress $progress): array
    {
        $total = 200;
        for ($i=0; $i <= $total; $i+=50) {
            usleep(1000);
            $progress->update(intval(($i/$total)*100), "Exported {$i}/{$total}");
        }
        return ['message' => 'Export complete', 'file' => '/storage/exports/users.csv'];
    }
}
