<?php

namespace App\Jobs;

use App\Services\LogFileProcessingLogger;
use App\Services\LogFileStreamingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessLogFileJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        private readonly string $filePath,
    ) {
    }

    public function handle(
        LogFileStreamingService $logFileStreamingService,
        LogFileProcessingLogger $processingLogger
    ): void {
        try {
            $processingLogger->started($this->filePath);
            $logFileStreamingService->process($this->filePath);
            $processingLogger->finished($this->filePath);
        } catch (Throwable $exception) {
            $processingLogger->internalError($this->filePath, $exception);

            Log::error($exception->getMessage(), [
                'exception' => $exception,
                'file_path' => $this->filePath,
            ]);

            throw $exception;
        }
    }
}
