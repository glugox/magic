<?php

use Glugox\Magic\Attributes\ActionDescription;
use Glugox\Magic\Contracts\DescribableAction;
use Glugox\Magic\Mcp\McpServer;
use Glugox\Magic\Support\ActionRegistry;
use Glugox\Magic\Support\BuildContext;
use Glugox\Magic\Traits\AsDescribableAction;

it('lists registered actions as MCP tools', function () {
    $registry = new ActionRegistry();
    $registry->register('stub_action', StubAction::class);

    $server = new McpServer(app(), $registry);

    $server->processIncoming(json_encode([
        'jsonrpc' => '2.0',
        'id' => 1,
        'method' => 'initialize',
        'params' => [],
    ]));

    $response = $server->processIncoming(json_encode([
        'jsonrpc' => '2.0',
        'id' => 2,
        'method' => 'tools/list',
        'params' => [],
    ]));

    expect($response)
        ->toHaveCount(1)
        ->and($response[0]['result']['tools'])
        ->toHaveCount(1)
        ->and($response[0]['result']['tools'][0]['name'])
        ->toBe('stub_action');
})->covers(StubAction::class);

it('executes an action through the MCP server', function () {
    $registry = new ActionRegistry();
    $registry->register('stub_action', StubAction::class);

    $server = new McpServer(app(), $registry);

    $server->processIncoming(json_encode([
        'jsonrpc' => '2.0',
        'id' => 1,
        'method' => 'initialize',
        'params' => [],
    ]));

    $response = $server->processIncoming(json_encode([
        'jsonrpc' => '2.0',
        'id' => 3,
        'method' => 'tools/call',
        'params' => [
            'name' => 'stub_action',
            'arguments' => [],
        ],
    ]));

    expect($response)
        ->toHaveCount(1)
        ->and($response[0]['result']['content'][0]['text'])
        ->toContain('executed successfully');
})->covers(StubAction::class);

#[ActionDescription(
    name: 'stub_action',
    description: 'A stub action used for MCP server tests.',
    parameters: [
        'config' => 'Optional path to a configuration file.',
    ],
)]
class StubAction implements DescribableAction
{
    use AsDescribableAction;

    public function __invoke(BuildContext $context): BuildContext
    {
        $context->registerGeneratedFile('example.txt');

        return $context;
    }
}
