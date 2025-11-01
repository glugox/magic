<?php

namespace Glugox\Actions\Tests;

use Glugox\Actions\ActionRunner;
use Glugox\Actions\Contracts\Action;
use Glugox\Actions\DTO\ActionContext;
use Glugox\Actions\Support\Progress;
use Glugox\Actions\ActionServiceProvider;
use Orchestra\Testbench\TestCase;

class ActionRunnerTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [ActionServiceProvider::class];
    }

    protected function defineDatabaseMigrations()
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }

    public function test_sync_run_completes_and_reaches_100()
    {
        $runner = $this->app->make(ActionRunner::class);
        $action = new class implements Action {
            public function name(): string { return 'Demo'; }
            public function handle(ActionContext $ctx, Progress $p): array {
                $p->update(50, 'Halfway');
                $p->update(100, 'Done');
                return ['message' => 'OK'];
            }
        };

        $run = $runner->run($action, params: ['x' => 1], userId: 1);

        $this->assertEquals('done', $run->status);
        $this->assertEquals(100, $run->progress);
        $this->assertEquals('Completed', $run->message);
    }
}
