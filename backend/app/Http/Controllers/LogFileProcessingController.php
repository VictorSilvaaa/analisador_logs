<?php

namespace App\Http\Controllers;

use App\Exceptions\UserVisibleException;
use App\Http\Requests\ProcessLogFileRequest;
use App\Services\LogFileProcessingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Throwable;

class LogFileProcessingController extends Controller
{
    private const LOG_FILES_DIRECTORY = 'app/private/logs';
    private const DEFAULT_LOG_FILE_NAME = 'logs.txt';

    public function __construct(protected LogFileProcessingService $logFileProcessingService)
    {
    }

    public function process(
        ProcessLogFileRequest $request,
    ): JsonResponse {
        try {
            $fileName = $request->validated('file_name') ?? self::DEFAULT_LOG_FILE_NAME;
            $filePath = storage_path(self::LOG_FILES_DIRECTORY . DIRECTORY_SEPARATOR . $fileName);

            if (! is_file($filePath) || ! is_readable($filePath)) {
                throw new UserVisibleException('O arquivo de logs informado e invalido ou nao pode ser lido.');
            }

            $this->logFileProcessingService->process($filePath);

            return response()->json([
                'message' => 'Processamento do arquivo de logs iniciado com sucesso.',
            ]);
        } catch (UserVisibleException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        } catch (Throwable $exception) {
            Log::error($exception->getMessage(), [
                'exception' => $exception,
            ]);

            return response()->json([
                'message' => 'Erro interno.',
            ], 500);
        }
    }
}
