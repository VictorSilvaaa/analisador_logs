<?php

namespace App\Services;

use App\Jobs\ProcessLogFileJob;

class LogFileProcessingService
{
    public function process(string $filePath): void
    {
        ProcessLogFileJob::dispatch($filePath);
    }
}
