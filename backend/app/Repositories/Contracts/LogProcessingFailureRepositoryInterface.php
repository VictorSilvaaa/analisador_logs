<?php

namespace App\Repositories\Contracts;

use App\DTOs\CreateLogProcessingFailureData;
use App\Models\LogProcessingFailure;

interface LogProcessingFailureRepositoryInterface
{
    public function create(CreateLogProcessingFailureData $data): LogProcessingFailure;

    public function markResolved(string $filePath, int $lineNumber, string $message): void;
}
