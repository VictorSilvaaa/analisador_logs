<?php

namespace App\Repositories\Contracts;

use App\DTOs\CreateRequestLogData;
use App\Models\RequestLog;

interface RequestLogRepositoryInterface
{
    public function create(CreateRequestLogData $data): RequestLog;
}
