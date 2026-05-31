<?php

namespace App\Repositories\Contracts;

use App\DTOs\CreateConsumerData;
use App\Models\Consumer;

interface ConsumerRepositoryInterface
{
    public function create(CreateConsumerData $data): Consumer;

    public function findByUuid(string $uuid): ?Consumer;

    public function firstOrCreate(CreateConsumerData $data): Consumer;

    public function upsertMany(array $rows): void;

    public function findIdsByUuids(array $uuids): array;
}
