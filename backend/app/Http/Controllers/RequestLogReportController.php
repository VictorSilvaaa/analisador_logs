<?php

namespace App\Http\Controllers;

use App\Services\RequestLogReportCsvService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class RequestLogReportController extends Controller
{
    public function __construct(
        private readonly RequestLogReportCsvService $reportCsvService,
    ) {
    }

    public function requestsByConsumer(): StreamedResponse|JsonResponse
    {
        try {
            return $this->reportCsvService->requestsByConsumer();
        } catch (Throwable $exception) {
            Log::error('Erro ao gerar relatório de requisições por consumidor.', [
                'error' => $exception->getMessage(),
                'exception' => $exception,
            ]);
            return response()->json(['message' => 'Erro interno ao gerar relatório.'], 500);
        }
    }

    public function requestsByService(): StreamedResponse|JsonResponse
    {
        try {
            return $this->reportCsvService->requestsByService();
        } catch (Throwable $exception) {
            Log::error('Erro ao gerar relatório de requisições por serviço.', [
                'error' => $exception->getMessage(),
                'exception' => $exception,
            ]);
            return response()->json(['message' => 'Erro interno ao gerar relatório.'], 500);
        }
    }

    public function averageLatenciesByService(): StreamedResponse|JsonResponse
    {
        try {
            return $this->reportCsvService->averageLatenciesByService();
        } catch (Throwable $exception) {
            Log::error('Erro ao gerar relatório de latências médias por serviço.', [
                'error' => $exception->getMessage(),
                'exception' => $exception,
            ]);
            return response()->json(['message' => 'Erro interno ao gerar relatório.'], 500);
        }
    }
}
