<?php

namespace Glugox\Magic\Commands;

use Glugox\Ai\AiManager;
use Glugox\Magic\Actions\Config\ResolveAppConfigAction;
use Illuminate\Support\Facades\Log;
use JsonException;
use ReflectionException;

class SuggestionsCommand extends MagicBaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'magic:suggestions
    {--config= : Path to JSON config file}
    {--starter= : Starter template to use}
    {--set=* : Inline config override in key=value format (dot notation allowed)}';

    protected $description = 'AI powered suggestions for improving your JSON config';

    /**
     * @throws ReflectionException
     * @throws JsonException
     */
    public function handle(): int
    {
        // Resolve config
        $config = app(ResolveAppConfigAction::class)($this->options());
        $jsonConfig = $config->toJson();

        $ai = new AiManager;

        $suggestionText = $ai->ask(
            "Please provide suggestions to improve the following JSON configuration for a Laravel application. Focus on best practices, potential issues, and enhancements that could be made:\n\n{$jsonConfig}\n\nSuggestions:"
        );

        // $suggestionText = $ai->ask("Hello!");

        $this->info("AI Suggestions:\n");
        $this->line($suggestionText->text());

        Log::channel('magic')->info('Suggestions complete!');

        return 0;
    }
}
