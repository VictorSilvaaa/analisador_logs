<?php

namespace App\DTOs;

readonly class CreateLogProcessingFailureData
{
    public function __construct(
        public string $filePath,
        public ?int $lineNumber,
        public ?string $content,
        public string $errorMessage,
        public ?array $context = null,
    ) {
    }

    public function toArray(): array
    {
        return [
            'file_path' => $this->filePath,
            'line_number' => $this->lineNumber,
            'content' => $this->content,
            'error_message' => $this->errorMessage,
            'context' => $this->context,
        ];
    }
}
