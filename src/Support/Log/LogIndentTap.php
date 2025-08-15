<?php

namespace Glugox\Magic\Support\Log;


use Monolog\Formatter\LineFormatter;
use Monolog\Logger as MonologLogger;

class LogIndentTap
{
    public function __invoke($monolog)
    {
        // Set the default indentation for all log messages
        foreach ($monolog->getHandlers() as $handler) {
            $formatter = $handler->getFormatter();

            // Only change LineFormatter — skip JSONFormatter
            if ($formatter instanceof LineFormatter) {
                $handler->setFormatter(new LineFormatter("  %message%\n", null, true, true));
            }
        }
    }
}
