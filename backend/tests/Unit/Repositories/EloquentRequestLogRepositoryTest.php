<?php

namespace Tests\Unit\Repositories;

use App\DTOs\CreateRequestLogData;
use App\Models\Consumer;
use App\Models\RequestLog;
use App\Models\Service;
use App\Repositories\EloquentRequestLogRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EloquentRequestLogRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private EloquentRequestLogRepository $repository;
    private Consumer $consumer;
    private Service $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new EloquentRequestLogRepository();
        $this->consumer = Consumer::query()->create(['uuid' => '80f74eef-31b8-45d5-c525-ae532297ea8e']);
        $this->service = Service::query()->create([
            'external_id' => '0590139e-7481-466c-bcdf-929adcaaf804',
            'name' => 'orders',
            'host' => 'orders.example.com',
            'port' => 80,
            'protocol' => 'http',
        ]);
    }

    // Cobre a criacao via DTO e a conversao de started_at em milissegundos para data e hora.
    public function test_create_persists_request_log_with_datetime_started_at(): void
    {
        $requestLog = $this->repository->create(new CreateRequestLogData(
            sourceFilePath: 'logs/import.txt',
            sourceLineNumber: 1,
            method: 'GET',
            uri: '/orders',
            url: 'http://orders.example.com/orders',
            responseStatus: 200,
            startedAt: 1433209822425,
            consumerId: $this->consumer->id,
            serviceId: $this->service->id,
        ));

        $this->assertSame('2015-06-01 22:50:22', $requestLog->refresh()->started_at->format('Y-m-d H:i:s'));
    }

    // Cobre o insert linha a linha do fallback e a serializacao das colunas JSON.
    public function test_create_from_array_persists_json_columns(): void
    {
        $requestLog = $this->repository->createFromArray($this->requestRow([
            'request_headers' => ['accept' => 'application/json'],
            'response_headers' => ['server' => 'nginx'],
            'querystring' => ['page' => '1'],
        ]));

        $this->assertSame(['accept' => 'application/json'], $requestLog->request_headers);
        $this->assertSame(['server' => 'nginx'], $requestLog->response_headers);
        $this->assertSame(['page' => '1'], $requestLog->querystring);
    }

    // Cobre a decisao de ignorar duplicados por arquivo e linha em reprocessamentos.
    public function test_create_from_array_ignores_duplicate_source_file_and_line(): void
    {
        $row = $this->requestRow(['source_file_path' => 'logs/import.txt', 'source_line_number' => 1]);

        $first = $this->repository->createFromArray($row);
        $second = $this->repository->createFromArray(array_merge($row, ['method' => 'POST']));

        $this->assertTrue($first->is($second));
        $this->assertSame('GET', $second->method);
        $this->assertDatabaseCount('requests', 1);
    }

    // Cobre o insert em lote usado no processamento de chunks.
    public function test_insert_many_persists_multiple_request_logs(): void
    {
        $this->repository->insertMany([
            $this->requestRow(['source_line_number' => 1]),
            $this->requestRow(['source_line_number' => 2, 'method' => 'POST']),
        ]);

        $this->assertDatabaseCount('requests', 2);
        $this->assertDatabaseHas('requests', [
            'source_line_number' => 2,
            'method' => 'POST',
        ]);
    }

    // Cobre o relatorio que agrupa total de requests por consumer.
    public function test_count_by_consumer_returns_totals_grouped_by_consumer_uuid(): void
    {
        $otherConsumer = Consumer::query()->create(['uuid' => '90f74eef-31b8-45d5-c525-ae532297ea8e']);
        $this->createRequestLog(lineNumber: 1, consumer: $this->consumer);
        $this->createRequestLog(lineNumber: 2, consumer: $this->consumer);
        $this->createRequestLog(lineNumber: 3, consumer: $otherConsumer);

        $rows = $this->repository->countByConsumer()->map(fn ($row): array => [
            'consumer_id' => $row->consumer_id,
            'total_requests' => (int) $row->total_requests,
        ])->all();

        $this->assertSame([
            ['consumer_id' => '80f74eef-31b8-45d5-c525-ae532297ea8e', 'total_requests' => 2],
            ['consumer_id' => '90f74eef-31b8-45d5-c525-ae532297ea8e', 'total_requests' => 1],
        ], $rows);
    }

    // Cobre o relatorio que agrupa total de requests por nome do service.
    public function test_count_by_service_returns_totals_grouped_by_service_name(): void
    {
        $tracking = Service::query()->create([
            'external_id' => '1590139e-7481-466c-bcdf-929adcaaf804',
            'name' => 'tracking',
            'host' => 'tracking.example.com',
            'port' => 80,
            'protocol' => 'http',
        ]);
        $this->createRequestLog(lineNumber: 1, service: $this->service);
        $this->createRequestLog(lineNumber: 2, service: $this->service);
        $this->createRequestLog(lineNumber: 3, service: $tracking);

        $rows = $this->repository->countByService()->map(fn ($row): array => [
            'service_name' => $row->service_name,
            'total_requests' => (int) $row->total_requests,
        ])->all();

        $this->assertSame([
            ['service_name' => 'orders', 'total_requests' => 2],
            ['service_name' => 'tracking', 'total_requests' => 1],
        ], $rows);
    }

    // Cobre a media de latencias por service usada no CSV de performance.
    public function test_average_latencies_by_service_returns_averages(): void
    {
        $this->createRequestLog(lineNumber: 1, requestLatency: 100, proxyLatency: 40, gatewayLatency: 10);
        $this->createRequestLog(lineNumber: 2, requestLatency: 200, proxyLatency: 80, gatewayLatency: 20);

        $row = $this->repository->averageLatenciesByService()->first();

        $this->assertSame('orders', $row->service_name);
        $this->assertSame(150.0, (float) $row->average_request_latency);
        $this->assertSame(60.0, (float) $row->average_proxy_latency);
        $this->assertSame(15.0, (float) $row->average_gateway_latency);
    }

    private function requestRow(array $overrides = []): array
    {
        return array_merge([
            'consumer_id' => $this->consumer->id,
            'service_id' => $this->service->id,
            'source_file_path' => 'logs/import.txt',
            'source_line_number' => 1,
            'method' => 'GET',
            'uri' => '/orders',
            'url' => 'http://orders.example.com/orders',
            'response_status' => 200,
            'proxy_latency' => 50,
            'gateway_latency' => 10,
            'request_latency' => 100,
            'started_at' => 1433209822425,
        ], $overrides);
    }

    private function createRequestLog(
        int $lineNumber,
        ?Consumer $consumer = null,
        ?Service $service = null,
        int $requestLatency = 100,
        int $proxyLatency = 50,
        int $gatewayLatency = 10
    ): RequestLog {
        return RequestLog::query()->create($this->requestRow([
            'consumer_id' => ($consumer ?? $this->consumer)->id,
            'service_id' => ($service ?? $this->service)->id,
            'source_line_number' => $lineNumber,
            'request_latency' => $requestLatency,
            'proxy_latency' => $proxyLatency,
            'gateway_latency' => $gatewayLatency,
        ]));
    }
}
