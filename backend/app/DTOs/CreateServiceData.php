<?php

namespace App\DTOs;

readonly class CreateServiceData
{
    public function __construct(
        public string $externalId,
        public string $host,
        public int $port,
        public string $protocol,
        public ?string $name = null,
        public ?string $path = null,
        public ?int $connectTimeout = null,
        public ?int $readTimeout = null,
        public ?int $writeTimeout = null,
        public ?int $retries = null,
        public ?int $serviceCreatedAt = null,
        public ?int $serviceUpdatedAt = null,
    ) {
    }

    public function toArray(): array
    {
        return [
            'external_id' => $this->externalId,
            'name' => $this->name,
            'host' => $this->host,
            'path' => $this->path,
            'port' => $this->port,
            'protocol' => $this->protocol,
            'connect_timeout' => $this->connectTimeout,
            'read_timeout' => $this->readTimeout,
            'write_timeout' => $this->writeTimeout,
            'retries' => $this->retries,
            'service_created_at' => $this->serviceCreatedAt,
            'service_updated_at' => $this->serviceUpdatedAt,
        ];
    }
}
