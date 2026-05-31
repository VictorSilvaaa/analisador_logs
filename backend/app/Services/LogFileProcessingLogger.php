<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;
use Throwable;

class LogFileProcessingLogger
{
    private const LOG_FILE_PATH = 'logs/log-file-processing.log';

    public function started(string $filePath): void
    {
        $this->logger()->info('Processamento do arquivo de logs iniciado.', [
            'file_name' => basename($filePath),
        ]);
    }

    public function finished(string $filePath): void
    {
        $this->logger()->info('Processamento do arquivo de logs finalizado.', [
            'file_name' => basename($filePath),
        ]);
    }

    public function internalError(string $filePath, Throwable $exception): void
    {
        $this->logger()->error('Erro interno durante o processamento do arquivo de logs.', [
            'file_name' => basename($filePath),
            'error' => $exception->getMessage(),
        ]);
    }

    private function logger(): LoggerInterface
    {
        return Log::build([
            'driver' => 'single',
            'path' => storage_path(self::LOG_FILE_PATH),
        ]);
    }
}
