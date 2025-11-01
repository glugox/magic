<?php

namespace Glugox\Magic\Tests;

use Glugox\Magic\MagicServiceProvider;
use Glugox\Magic\Support\BuildContext;
use Glugox\Magic\Support\Config\Config;
use Illuminate\Support\Facades\File;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    private string $tmpDir;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure each parallel process has its own tmp dir
        $processToken = getenv('TEST_TOKEN') ?: 'default'; // each parallel worker has a unique token
        $this->tmpDir = __DIR__.'/.tmp/'.$processToken;

        File::ensureDirectoryExists($this->tmpDir);

        // This make sure that when we call base_path() in the tests, it uses the tmp dir
        // It also makes sure that any published files (like migrations) go to the tmp dir (database_path, etc.)
        $this->app->setBasePath($this->tmpDir);

        // Set to show less debug messages during tests
        config()->set('logging.channels.magic_console.level', 'error');
    }

    /**
     * Cleanup directories
     */
    protected function tearDown(): void
    {
        // Cleanup only *this process’s* tmp dir
        if (is_dir($this->tmpDir)) {
            File::deleteDirectory($this->tmpDir);
        }

        parent::tearDown();
    }

    /**
     * Creates Config from a config file in the Data directory.
     */
    public function createConfigFromFile(string $file): Config
    {
        $configJsonPath = __DIR__."/data/{$file}";

        return Config::fromJsonFile($configJsonPath);
    }

    /**
     * Load a test config from the Data directory.
     */
    public function createBuildContextFromFile(string $filename): BuildContext
    {
        $configJsonPath = __DIR__."/data/{$filename}";
        $config = Config::fromJsonFile($configJsonPath);
        $buildContext = BuildContext::fromOptions([
            'config' => $configJsonPath,
        ])->setConfig($config);

        return $buildContext;
    }

    public function getEnvironmentSetUp($app): void
    {
        config()->set('database.default', 'testing');
    }

    protected function getPackageProviders($app)
    {
        return [
            MagicServiceProvider::class,
            \Glugox\Builder\BuilderServiceProvider::class,
            \Glugox\Core\CoreServiceProvider::class,
        ];
    }
}
