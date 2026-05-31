<?php

namespace App\Repositories;

use App\DTOs\CreateConsumerData;
use App\Models\Consumer;
use App\Repositories\Contracts\ConsumerRepositoryInterface;

class EloquentConsumerRepository implements ConsumerRepositoryInterface
{
    public function create(CreateConsumerData $data): Consumer
    {
        return Consumer::create($data->toArray());
    }

    public function findByUuid(string $uuid): ?Consumer
    {
        return Consumer::query()
            ->where('uuid', $uuid)
            ->first();
    }

    public function firstOrCreate(CreateConsumerData $data): Consumer
    {
        return Consumer::query()->firstOrCreate(
            ['uuid' => $data->uuid],
            $data->toArray(),
        );
    }
}
