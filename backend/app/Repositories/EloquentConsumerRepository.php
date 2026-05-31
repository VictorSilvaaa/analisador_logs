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

    public function upsertMany(array $rows): void
    {
        if ($rows === []) {
            return;
        }

        $now = now();

        $rows = array_map(fn (array $row): array => [
            'uuid' => $row['uuid'],
            'created_at' => $now,
            'updated_at' => $now,
        ], $rows);

        Consumer::query()->upsert(
            $rows,
            ['uuid'],
            ['updated_at'],
        );
    }

    public function findIdsByUuids(array $uuids): array
    {
        return Consumer::query()
            ->whereIn('uuid', $uuids)
            ->pluck('id', 'uuid')
            ->all();
    }
}
