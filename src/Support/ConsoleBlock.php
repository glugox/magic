<?php

namespace Glugox\Magic\Support;

use Illuminate\Console\Command;

class ConsoleBlock
{
    protected Command $command;

    public function __construct(Command $command)
    {
        $this->command = $command;
    }

    /**
     * Print an INFO block (blue label)
     */
    public function info(string $message): void
    {
        $this->block($message, 'INFO', 'white', 'blue');
    }

    /**
     * Print a WARN block (yellow label)
     */
    public function warn(string $message): void
    {
        $this->block($message, 'WARN', 'black', 'yellow');
    }

    /**
     * Print a SUCCESS block (green label)
     */
    public function success(string $message): void
    {
        $this->block($message, 'SUCCESS', 'white', 'green');
    }

    /**
     * Print an ERROR block (red label)
     */
    public function error(string $message): void
    {
        $this->block($message, 'ERROR', 'white', 'red');
    }

    /**
     * Generic block renderer
     */
    public function block(string $message, string $label, string $fg, string $bg): void
    {
        $this->command->line('  ');
        $this->command->line("  <fg={$fg};bg={$bg}> {$label} </>  {$message}");
        $this->command->line('  ');
    }
}
