<?php

namespace App\Services;

use App\Jobs\ProcessLogFileChunkJob;
use RuntimeException;

class LogFileStreamingService
{
    public const CHUNK_SIZE = 400;

    public function process(string $filePath): void
    {
        $handle = fopen($filePath, 'rb');

        if ($handle === false) {
            throw new RuntimeException('Nao foi possivel abrir o arquivo de logs para leitura.');
        }

        try {
            $chunk = [];
            $lineNumber = 0;

            // Leitura em streaming
            while (($line = fgets($handle)) !== false) {
                $lineNumber++;
                $chunk[] = [
                    'line_number' => $lineNumber,
                    'content' => rtrim($line, "\r\n"),
                ];

                if (count($chunk) >= self::CHUNK_SIZE) {
                    // Cada chunk segue para outro job, liberando o job atual para continuar lendo
                    ProcessLogFileChunkJob::dispatch($filePath, $chunk);
                    $chunk = [];
                }
            }

            if (! feof($handle)) {
                throw new RuntimeException('Nao foi possivel concluir a leitura do arquivo de logs.');
            }

            if ($chunk !== []) {
                ProcessLogFileChunkJob::dispatch($filePath, $chunk);
            }
        } finally {
            fclose($handle);
        }
    }
}
