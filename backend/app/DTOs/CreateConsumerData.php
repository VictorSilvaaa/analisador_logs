<?php

namespace App\DTOs;

readonly class CreateConsumerData
{
    public function __construct(
        public string $uuid,
    ) {
    }

    public function toArray(): array
    {
        return [
            'uuid' => $this->uuid,
        ];
    }
}
