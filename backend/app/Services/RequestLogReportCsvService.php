<?php

namespace App\Services;

use App\Repositories\Contracts\RequestLogRepositoryInterface;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RequestLogReportCsvService
{
    public function __construct(
        private readonly RequestLogRepositoryInterface $requestLogRepository,
    ) {
    }

    public function requestsByConsumer(): StreamedResponse
    {
        return $this->stream(
            filename: 'total-requests-by-consumer.csv',
            headers: ['consumer_id', 'total_requests'],
            rows: $this->requestLogRepository->countByConsumer(),
            mapRow: fn (object $row): array => [
                $row->consumer_id,
                $row->total_requests,
            ],
        );
    }

    public function requestsByService(): StreamedResponse
    {
        return $this->stream(
            filename: 'total-requests-by-service.csv',
            headers: ['service_name', 'total_requests'],
            rows: $this->requestLogRepository->countByService(),
            mapRow: fn (object $row): array => [
                $row->service_name,
                $row->total_requests,
            ],
        );
    }

    public function averageLatenciesByService(): StreamedResponse
    {
        return $this->stream(
            filename: 'average-latencies-by-service.csv',
            headers: [
                'service_name',
                'average_request_latency',
                'average_proxy_latency',
                'average_gateway_latency',
            ],
            rows: $this->requestLogRepository->averageLatenciesByService(),
            mapRow: fn (object $row): array => [
                $row->service_name,
                $this->formatDecimal($row->average_request_latency),
                $this->formatDecimal($row->average_proxy_latency),
                $this->formatDecimal($row->average_gateway_latency),
            ],
        );
    }

    private function stream(string $filename, array $headers, iterable $rows, callable $mapRow): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows, $mapRow): void {
            $output = fopen('php://output', 'w');

            fputcsv($output, $headers);

            foreach ($rows as $row) {
                fputcsv($output, $mapRow($row));
            }

            fclose($output);
        }, $this->timestampedFilename($filename), [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function formatDecimal(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        return number_format((float) $value, 2, '.', '');
    }

    private function timestampedFilename(string $filename): string
    {
        $timestamp = Carbon::now()->format('Ymd_His');

        return "{$timestamp}_{$filename}";
    }
}
