<?php

namespace Glugox\Magic\Commands;

use Glugox\Magic\Mcp\McpServer;
use Glugox\Magic\Support\ActionRegistry;
use Illuminate\Console\Command;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Facades\Log;
use Throwable;

class McpServerCommand extends MagicBaseCommand
{
    protected $signature = 'magic:mcp-server
        {--config= : Path to JSON config file}
        {--starter= : Starter template to use}
        {--set=* : Inline config overrides in key=value format (dot notation allowed)}';

    protected $description = 'Start the Magic MCP server.';

    public function handle(ActionRegistry $registry, Container $container): int
    {
        $defaultOptions = [
            'config' => $this->option('config'),
            'starter' => $this->option('starter'),
            'overrides' => $this->option('set') ?? [],
        ];

        $server = new McpServer($container, $registry, $defaultOptions);

        $this->info('Starting Magic MCP server. Awaiting requests...');

        try {
            $server->run();
        } catch (Throwable $throwable) {
            Log::channel('magic')->error('MCP server stopped due to error: '.$throwable->getMessage());
            $this->error('MCP server stopped: '.$throwable->getMessage());

            return Command::FAILURE;
        }

        $this->info('Magic MCP server stopped.');

        return Command::SUCCESS;
    }
}
