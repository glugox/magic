<?php

namespace Glugox\Magic\Support\Log;

use Monolog\Logger;

class LogIndentTap
{
    /**
     * Apply processor to prepend indent to each log record.
     */
    public function __invoke(Logger $logger)
    {
        $logger->pushProcessor(function ($record) {
            $record['message'] = '  '.$record['message']; // prepend 2 spaces

            return $record;
        });
    }
}
