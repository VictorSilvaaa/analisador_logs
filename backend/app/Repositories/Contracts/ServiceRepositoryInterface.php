<?php

namespace App\Repositories\Contracts;

use App\DTOs\CreateServiceData;
use App\Models\Service;

interface ServiceRepositoryInterface
{
    public function create(CreateServiceData $data): Service;

    public function findByExternalId(string $externalId): ?Service;

    public function updateOrCreate(CreateServiceData $data): Service;
}
