<?php

namespace App\Repositories;

use App\DTOs\CreateLogProcessingFailureData;
use App\Models\LogProcessingFailure;
use App\Repositories\Contracts\LogProcessingFailureRepositoryInterface;

class EloquentLogProcessingFailureRepository implements LogProcessingFailureRepositoryInterface
{
    public function create(CreateLogProcessingFailureData $data): LogProcessingFailure
    {
        return LogProcessingFailure::create($data->toArray());
    }

    public function markResolved(string $filePath, int $lineNumber, string $message): void
    {
        LogProcessingFailure::query()
            ->where('file_path', $filePath)
            ->where('line_number', $lineNumber)
            ->whereNull('resolved_at')
            ->update([
                'resolved_at' => now(),
                'resolved_message' => $message,
            ]);
    }
}
