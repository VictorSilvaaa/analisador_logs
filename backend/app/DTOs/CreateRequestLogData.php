<?php

namespace App\DTOs;

readonly class CreateRequestLogData
{
    public function __construct(
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
            'started_at' => $this->startedAt,
            'request_headers' => $this->requestHeaders,
            'response_headers' => $this->responseHeaders,
            'querystring' => $this->querystring,
        ];
    }
}
