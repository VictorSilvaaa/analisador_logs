<?php

namespace App\Repositories;

use App\DTOs\CreateRequestLogData;
use App\Models\RequestLog;
use App\Repositories\Contracts\RequestLogRepositoryInterface;

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

    private function prepareRows(array $rows): array
    {
        $now = now();

        return array_map(function (array $row) use ($now): array {
            $row['request_headers'] = $this->encodeJsonColumn($row['request_headers'] ?? null);
            $row['response_headers'] = $this->encodeJsonColumn($row['response_headers'] ?? null);
            $row['querystring'] = $this->encodeJsonColumn($row['querystring'] ?? null);
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
}
