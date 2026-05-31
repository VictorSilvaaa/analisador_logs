<?php

namespace App\DTOs;

use Carbon\CarbonImmutable;

readonly class CreateRequestLogData
{
    public function __construct(
        public string $sourceFilePath,
        public int $sourceLineNumber,
        public string $method,
        public string $uri,
        public string $url,
        public int $responseStatus,
        public int $startedAt,
        public ?int $consumerId = null,
        public ?int $serviceId = null,
        public ?int $requestSize = null,
        public ?string $upstreamUri = null,
        public ?int $responseSize = null,
        public ?int $proxyLatency = null,
        public ?int $gatewayLatency = null,
        public ?int $requestLatency = null,
        public ?string $clientIp = null,
        public ?array $requestHeaders = null,
        public ?array $responseHeaders = null,
        public ?array $querystring = null,
    ) {
    }

    public function toArray(): array
    {
        return [
            'consumer_id' => $this->consumerId,
            'service_id' => $this->serviceId,
            'source_file_path' => $this->sourceFilePath,
            'source_line_number' => $this->sourceLineNumber,
            'method' => $this->method,
            'uri' => $this->uri,
            'url' => $this->url,
            'request_size' => $this->requestSize,
            'upstream_uri' => $this->upstreamUri,
            'response_status' => $this->responseStatus,
            'response_size' => $this->responseSize,
            'proxy_latency' => $this->proxyLatency,
            'gateway_latency' => $this->gatewayLatency,
            'request_latency' => $this->requestLatency,
            'client_ip' => $this->clientIp,
            'started_at' => $this->normalizeTimestamp($this->startedAt),
            'request_headers' => $this->requestHeaders,
            'response_headers' => $this->responseHeaders,
            'querystring' => $this->querystring,
        ];
    }

    private function normalizeTimestamp(int $timestamp): string
    {
        if ($timestamp > 9999999999) {
            $timestamp = (int) floor($timestamp / 1000);
        }

        return CarbonImmutable::createFromTimestamp($timestamp, config('app.timezone'))->toDateTimeString();
    }
}
