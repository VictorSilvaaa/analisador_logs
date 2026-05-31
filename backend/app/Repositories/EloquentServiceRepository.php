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
}
