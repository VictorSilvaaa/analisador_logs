<?php

namespace App\Jobs;

use App\Services\LogFileChunkProcessingService;
use App\Services\LogFileProcessingLogger;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessLogFileChunkJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        private readonly string $filePath,
        private readonly array $lines,
    ) {
    }

    public function handle(
        LogFileChunkProcessingService $chunkProcessingService,
        LogFileProcessingLogger $processingLogger
    ): void {
        try {
            $chunkProcessingService->process($this->filePath, $this->lines);
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
