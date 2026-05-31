<?php

namespace App\Repositories;

use App\DTOs\CreateRequestLogData;
use App\Models\RequestLog;
use App\Repositories\Contracts\RequestLogRepositoryInterface;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\LazyCollection;

class EloquentRequestLogRepository implements RequestLogRepositoryInterface
{
    public function create(CreateRequestLogData $data): RequestLog
    {
        return RequestLog::create($data->toArray());
    }

    public function createFromArray(array $data): RequestLog
    {
        RequestLog::query()->insertOrIgnore($this->prepareRows([$data]));

        return RequestLog::query()
            ->where('source_file_path', $data['source_file_path'])
            ->where('source_line_number', $data['source_line_number'])
            ->firstOrFail();
    }

    public function insertMany(array $rows): void
    {
        if ($rows === []) {
            return;
        }

        RequestLog::query()->insertOrIgnore($this->prepareRows($rows));
    }

    public function countByConsumer(): LazyCollection
    {
        return RequestLog::query()
            ->join('consumers', 'consumers.id', '=', 'requests.consumer_id')
            ->select([
                'consumers.uuid as consumer_id',
                DB::raw('COUNT(requests.id) as total_requests'),
            ])
            ->groupBy('consumers.uuid')
            ->orderBy('consumers.uuid')
            ->cursor();
    }

    public function countByService(): LazyCollection
    {
        return RequestLog::query()
            ->join('services', 'services.id', '=', 'requests.service_id')
            ->select([
                'services.name as service_name',
                DB::raw('COUNT(requests.id) as total_requests'),
            ])
            ->groupBy('services.name')
            ->orderBy('services.name')
            ->cursor();
    }

    public function averageLatenciesByService(): LazyCollection
    {
        return RequestLog::query()
            ->join('services', 'services.id', '=', 'requests.service_id')
            ->select([
                'services.name as service_name',
                DB::raw('AVG(requests.request_latency) as average_request_latency'),
                DB::raw('AVG(requests.proxy_latency) as average_proxy_latency'),
                DB::raw('AVG(requests.gateway_latency) as average_gateway_latency'),
            ])
            ->groupBy('services.name')
            ->orderBy('services.name')
            ->cursor();
    }

    private function prepareRows(array $rows): array
    {
        $now = now();

        return array_map(function (array $row) use ($now): array {
            $row['request_headers'] = $this->encodeJsonColumn($row['request_headers'] ?? null);
            $row['response_headers'] = $this->encodeJsonColumn($row['response_headers'] ?? null);
            $row['querystring'] = $this->encodeJsonColumn($row['querystring'] ?? null);
            $row['started_at'] = $this->normalizeStartedAt((int) $row['started_at']);
            $row['processed_at'] = $row['processed_at'] ?? $now;
            $row['created_at'] = $row['created_at'] ?? $now;
            $row['updated_at'] = $row['updated_at'] ?? $now;

            return $row;
        }, $rows);
    }

    private function encodeJsonColumn(?array $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return json_encode($value, JSON_THROW_ON_ERROR);
    }

    private function normalizeStartedAt(int $timestamp): string
    {
        if ($timestamp > 9999999999) {
            $timestamp = (int) floor($timestamp / 1000);
        }

        return CarbonImmutable::createFromTimestamp($timestamp, config('app.timezone'))->toDateTimeString();
    }
}
