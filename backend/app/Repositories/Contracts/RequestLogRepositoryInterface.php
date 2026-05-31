<?php

namespace App\Repositories\Contracts;

use App\DTOs\CreateRequestLogData;
use App\Models\RequestLog;

interface RequestLogRepositoryInterface
{
    public function create(CreateRequestLogData $data): RequestLog;

    public function createFromArray(array $data): RequestLog;

    public function insertMany(array $rows): void;
}
