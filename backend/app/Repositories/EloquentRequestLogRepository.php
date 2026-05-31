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
}
