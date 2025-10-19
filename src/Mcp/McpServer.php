<?php

namespace Glugox\Magic\Mcp;

use Glugox\Magic\Support\ActionDescriptionData;
use Glugox\Magic\Support\ActionRegistry;
use Glugox\Magic\Support\BuildContext;
use Glugox\Magic\Support\File\FilesGenerationUpdate;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Facades\Log;
use JsonException;
use RuntimeException;

class McpServer
{
    private bool $initialized = false;

    /**
     * @var array<string, mixed>
     */
    private array $sessionOptions;

    /**
     * @param  array<string, mixed>  $defaultOptions
     */
    public function __construct(
        private readonly Container $container,
        private readonly ActionRegistry $registry,
        array $defaultOptions = []
    ) {
        $this->sessionOptions = $this->mergeOptions($defaultOptions);
    }

    public function run(): void
    {
        $stdin = fopen('php://stdin', 'r');

        if ($stdin === false) {
            throw new RuntimeException('Unable to open STDIN for MCP server.');
        }

        stream_set_blocking($stdin, false);
        $buffer = '';

        while (! feof($stdin)) {
            $read = [$stdin];
            $write = null;
            $except = null;

            $ready = stream_select($read, $write, $except, null);

            if ($ready === false) {
                break;
            }

            if ($ready === 0) {
                continue;
            }

            foreach ($read as $stream) {
                $chunk = fgets($stream);

                if ($chunk === false) {
                    continue;
                }

                $buffer .= $chunk;

                while (($pos = strpos($buffer, "\n")) !== false) {
                    $line = trim(substr($buffer, 0, $pos));
                    $buffer = substr($buffer, $pos + 1);

                    if ($line === '') {
                        continue;
                    }

                    foreach ($this->processIncoming($line) as $message) {
                        $this->sendMessage($message);
                    }
                }
            }
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function processIncoming(string $payload): array
    {
        try {
            /** @var array<string, mixed>|null $message */
            $message = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            Log::channel('magic')->warning('Invalid MCP payload received: '.$exception->getMessage());

            return [$this->formatError(null, -32700, 'Invalid JSON payload.')];
        }

        if (! is_array($message)) {
            return [$this->formatError(null, -32600, 'Invalid request payload.')];
        }

        if (! isset($message['method'])) {
            // Ignore responses from the client.
            return [];
        }

        $method = (string) $message['method'];
        $id = $message['id'] ?? null;
        $params = $message['params'] ?? [];

        return match ($method) {
            'initialize' => $this->handleInitialize($id, $params),
            'ping' => [$this->formatResponse($id, ['message' => 'pong'])],
            'tools/list' => [$this->handleListTools($id)],
            'tools/describe' => [$this->handleDescribeTool($id, $params)],
            'tools/call' => [$this->handleCallTool($id, $params)],
            default => [$this->formatError($id, -32601, "Unknown method: {$method}")],
        };
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function handleInitialize(mixed $id, mixed $params): array
    {
        $this->initialized = true;
        $this->sessionOptions = $this->mergeOptions(is_array($params) ? $params : []);

        $response = $this->formatResponse($id, [
            'protocolVersion' => '2024-05-01',
            'serverInfo' => [
                'name' => 'glugox-magic',
                'version' => $this->resolvePackageVersion(),
            ],
            'capabilities' => [
                'tools' => [
                    'list' => true,
                    'call' => true,
                    'describe' => true,
                ],
            ],
        ]);

        $notification = $this->formatNotification('initialized', []);

        return [$response, $notification];
    }

    private function handleListTools(mixed $id): array
    {
        $tools = array_map(
            fn (string $alias) => $this->describeTool($alias),
            array_keys($this->registry->all())
        );

        return $this->formatResponse($id, [
            'tools' => array_values(array_filter($tools)),
        ]);
    }

    private function handleDescribeTool(mixed $id, mixed $params): array
    {
        $name = is_array($params) ? ($params['name'] ?? null) : null;

        if (! is_string($name)) {
            return $this->formatError($id, -32602, 'The tool name must be provided.');
        }

        $description = $this->describeTool($name);

        if ($description === null) {
            return $this->formatError($id, -32601, "Unknown tool: {$name}");
        }

        return $this->formatResponse($id, [
            'tool' => $description,
        ]);
    }

    private function handleCallTool(mixed $id, mixed $params): array
    {
        if (! $this->initialized) {
            return $this->formatError($id, -32000, 'Server has not been initialized.');
        }

        if (! is_array($params)) {
            return $this->formatError($id, -32602, 'Invalid call parameters.');
        }

        $name = $params['name'] ?? null;
        if (! is_string($name)) {
            return $this->formatError($id, -32602, 'Tool name must be a string.');
        }

        $arguments = $params['arguments'] ?? [];
        if (! is_array($arguments)) {
            return $this->formatError($id, -32602, 'Tool arguments must be an object.');
        }

        $actionClass = $this->registry->get($name);
        if ($actionClass === null) {
            return $this->formatError($id, -32601, "Unknown tool: {$name}");
        }

        try {
            $context = BuildContext::fromOptions($this->mergeOptions($arguments));
            $action = $this->container->make($actionClass);

            $result = $action($context);

            if ($result instanceof BuildContext) {
                $context = $result;
            }

            $content = $this->buildSuccessContent($name, $context);

            return $this->formatResponse($id, [
                'content' => $content,
            ]);
        } catch (JsonException $exception) {
            Log::channel('magic')->error("MCP action '{$name}' failed: {$exception->getMessage()}");

            return $this->formatError($id, -32700, $exception->getMessage());
        } catch (RuntimeException $exception) {
            Log::channel('magic')->error("MCP action '{$name}' failed: {$exception->getMessage()}");

            return $this->formatError($id, -32000, $exception->getMessage());
        } catch (\Throwable $exception) {
            Log::channel('magic')->error("MCP action '{$name}' crashed: {$exception->getMessage()}");

            return $this->formatError($id, -32001, 'Unexpected error: '.$exception->getMessage());
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    private function describeTool(string $alias): ?array
    {
        $class = $this->registry->get($alias);

        if ($class === null) {
            return null;
        }

        $action = $this->container->make($class);

        if (! method_exists($action, 'describe')) {
            return null;
        }

        /** @var ActionDescriptionData $description */
        $description = $action->describe();

        $properties = [];
        foreach ($description->parameters as $name => $parameterDescription) {
            $properties[$name] = [
                'type' => 'string',
                'description' => $parameterDescription,
            ];
        }

        return [
            'name' => $alias,
            'description' => $description->description,
            'input_schema' => [
                'type' => 'object',
                'properties' => $properties,
                'additionalProperties' => true,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    private function mergeOptions(array $options): array
    {
        $merged = $this->sessionOptions ?? [];

        foreach (['config', 'starter'] as $key) {
            if (array_key_exists($key, $options) && $options[$key] !== null) {
                $merged[$key] = $options[$key];
            }
        }

        $overrides = $options['overrides'] ?? $options['set'] ?? null;
        if ($overrides !== null) {
            $merged['overrides'] = $this->normalizeOverrides($overrides);
        }

        if (! isset($merged['overrides'])) {
            $merged['overrides'] = [];
        }

        return $merged;
    }

    /**
     * @param  array<int|string, mixed>|string  $overrides
     * @return string[]
     */
    private function normalizeOverrides(array|string $overrides): array
    {
        if (is_string($overrides)) {
            return [$overrides];
        }

        $normalized = [];
        foreach ($overrides as $override) {
            if (is_string($override)) {
                $normalized[] = $override;
            }
        }

        return $normalized;
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function buildSuccessContent(string $name, BuildContext $context): array
    {
        $content = [[
            'type' => 'text',
            'text' => "Action '{$name}' executed successfully.",
        ]];

        if ($context->hasErrors()) {
            $content[] = [
                'type' => 'text',
                'text' => "Errors:\n".$context->error(),
            ];
        }

        $files = $this->formatFilesUpdate($context->getFilesGenerationUpdate());
        if ($files !== null) {
            $content[] = [
                'type' => 'text',
                'text' => $files,
            ];
        }

        return $content;
    }

    private function formatFilesUpdate(FilesGenerationUpdate $update): ?string
    {
        if (empty($update->created) && empty($update->updated) && empty($update->deleted) && empty($update->folders)) {
            return null;
        }

        $lines = ['File summary:'];

        if (! empty($update->created)) {
            $lines[] = ' - created: '.implode(', ', $update->created);
        }

        if (! empty($update->updated)) {
            $lines[] = ' - updated: '.implode(', ', $update->updated);
        }

        if (! empty($update->deleted)) {
            $lines[] = ' - deleted: '.implode(', ', $update->deleted);
        }

        if (! empty($update->folders)) {
            $lines[] = ' - folders: '.implode(', ', $update->folders);
        }

        return implode("\n", $lines);
    }

    private function resolvePackageVersion(): string
    {
        $composerPath = dirname(__DIR__, 2).'/composer.json';

        if (! file_exists($composerPath)) {
            return 'dev';
        }

        $composer = json_decode(file_get_contents($composerPath) ?: '[]', true);

        if (is_array($composer) && isset($composer['version']) && is_string($composer['version'])) {
            return $composer['version'];
        }

        return $composer['extra']['branch-alias']['dev-main'] ?? 'dev';
    }

    private function sendMessage(array $message): void
    {
        $encoded = json_encode($message, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if ($encoded === false) {
            return;
        }

        fwrite(STDOUT, $encoded."\n");
        fflush(STDOUT);
    }

    private function formatResponse(mixed $id, array $result): array
    {
        return [
            'jsonrpc' => '2.0',
            'id' => $id,
            'result' => $result,
        ];
    }

    private function formatError(mixed $id, int $code, string $message): array
    {
        return [
            'jsonrpc' => '2.0',
            'id' => $id,
            'error' => [
                'code' => $code,
                'message' => $message,
            ],
        ];
    }

    private function formatNotification(string $method, mixed $params): array
    {
        return [
            'jsonrpc' => '2.0',
            'method' => $method,
            'params' => $params,
        ];
    }
}
