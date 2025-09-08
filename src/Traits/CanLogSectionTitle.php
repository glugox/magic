<?php

namespace Glugox\Magic\Traits;

use Illuminate\Support\Facades\Log;

trait CanLogSectionTitle
{
    /**
     * Logs the start of an action with a formatted title.
     */
    public function logInvocation(string $title): void
    {
        Log::channel('magic')->info('');
        Log::channel('magic')->info('======================================================================');
        Log::channel('magic')->info("========== START: $title");
        Log::channel('magic')->info('======================================================================');
        Log::channel('magic')->info('');
    }
}
