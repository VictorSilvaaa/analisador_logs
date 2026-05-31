<?php

namespace App\Repositories;

use App\DTOs\CreateServiceData;
use App\Models\Service;
use App\Repositories\Contracts\ServiceRepositoryInterface;

class EloquentServiceRepository implements ServiceRepositoryInterface
{
    public function create(CreateServiceData $data): Service
    {
        return Service::create($data->toArray());
    }

    public function findByExternalId(string $externalId): ?Service
    {
        return Service::query()
            ->where('external_id', $externalId)
            ->first();
    }

    public function updateOrCreate(CreateServiceData $data): Service
    {
        return Service::query()->updateOrCreate(
            ['external_id' => $data->externalId],
            $data->toArray(),
        );
    }

    public function upsertMany(array $rows): void
    {
        if ($rows === []) {
            return;
        }

        $now = now();

        $rows = array_map(function (array $row) use ($now): array {
            $row['created_at'] = $now;
            $row['updated_at'] = $now;

            return $row;
        }, $rows);

        Service::query()->upsert(
            $rows,
            ['external_id'],
            [
                'name',
                'host',
                'path',
                'port',
                'protocol',
                'connect_timeout',
                'read_timeout',
                'write_timeout',
                'retries',
                'service_created_at',
                'service_updated_at',
                'updated_at',
            ],
        );
    }

    public function findIdsByExternalIds(array $externalIds): array
    {
        return Service::query()
            ->whereIn('external_id', $externalIds)
            ->pluck('id', 'external_id')
            ->all();
    }
}
