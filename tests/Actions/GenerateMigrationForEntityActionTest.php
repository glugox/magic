<?php

use Glugox\Magic\Actions\Build\Migration\GenerateMigrationForEntityAction;

use function Glugox\Magic\Tests\Helpers\makeDummyProductEntityConfig;

beforeEach(function () {
    config()->set('logging.channels.magic_console.level', 'error');
});

it('generates migration for entity', function () {

    // Set up a dummy entity configuration
    $entity = makeDummyProductEntityConfig();

    // Action
    app(GenerateMigrationForEntityAction::class)($entity);

    $files = glob(database_path('migrations/*_create_products_table.php'));
    $this->assertNotEmpty($files, 'Migration file not found');

    $migrationContent = file_get_contents($files[0]);

    $this->assertStringContainsString('Schema::create(\'products\'', $migrationContent);
    $this->assertStringContainsString('$table->string(\'title\')', $migrationContent);
    $this->assertStringContainsString('$table->text(\'description\')', $migrationContent);
    $this->assertStringContainsString('$table->decimal(\'price\', 10, 2)', $migrationContent);
    $this->assertStringContainsString('$table->integer(\'stock\')->default(0)', $migrationContent);
    $this->assertStringContainsString('$table->uuid(\'sku\')', $migrationContent);
    $this->assertStringContainsString('$table->boolean(\'is_active\')->default(true)', $migrationContent);
    $this->assertStringContainsString('$table->json(\'tags\')', $migrationContent);
    $this->assertStringContainsString('$table->enum(\'category\', [\'electronics\', \'books\', \'clothing\', \'home\'])', $migrationContent);
    $this->assertStringContainsString('$table->dateTime(\'released_at\')->nullable()', $migrationContent);
    $this->assertStringContainsString('$table->string(\'image\')->nullable()', $migrationContent);
});
