<?php

namespace Tests\Feature;

use App\Models\Consumer;
use App\Models\RequestLog;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RequestLogReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_exports_total_requests_grouped_by_consumer(): void
    {
        $consumerA = Consumer::query()->create(['uuid' => '80f74eef-31b8-45d5-c525-ae532297ea8e']);
        $consumerB = Consumer::query()->create(['uuid' => '90f74eef-31b8-45d5-c525-ae532297ea8e']);
        $service = $this->createService('orders');

        $this->createRequestLog($consumerA, $service, 1);
        $this->createRequestLog($consumerA, $service, 2);
        $this->createRequestLog($consumerB, $service, 3);

        $response = $this->get('/api/reports/requests-by-consumer');

        $response->assertOk();
        $this->assertSame(
            "consumer_id,total_requests\n"
            . "80f74eef-31b8-45d5-c525-ae532297ea8e,2\n"
            . "90f74eef-31b8-45d5-c525-ae532297ea8e,1\n",
            $response->streamedContent(),
        );
    }

    public function test_exports_total_requests_grouped_by_service(): void
    {
        $consumer = Consumer::query()->create(['uuid' => '80f74eef-31b8-45d5-c525-ae532297ea8e']);
        $ordersService = $this->createService('orders');
        $trackingService = $this->createService('tracking');

        $this->createRequestLog($consumer, $ordersService, 1);
        $this->createRequestLog($consumer, $ordersService, 2);
        $this->createRequestLog($consumer, $trackingService, 3);

        $response = $this->get('/api/reports/requests-by-service');

        $response->assertOk();
        $this->assertSame(
            "service_name,total_requests\n"
            . "orders,2\n"
            . "tracking,1\n",
            $response->streamedContent(),
        );
    }

    public function test_exports_average_latencies_grouped_by_service(): void
    {
        $consumer = Consumer::query()->create(['uuid' => '80f74eef-31b8-45d5-c525-ae532297ea8e']);
        $service = $this->createService('orders');

        $this->createRequestLog($consumer, $service, 1, requestLatency: 100, proxyLatency: 40, gatewayLatency: 10);
        $this->createRequestLog($consumer, $service, 2, requestLatency: 200, proxyLatency: 80, gatewayLatency: 20);

        $response = $this->get('/api/reports/average-latencies-by-service');

        $response->assertOk();
        $this->assertSame(
            "service_name,average_request_latency,average_proxy_latency,average_gateway_latency\n"
            . "orders,150.00,60.00,15.00\n",
            $response->streamedContent(),
        );
    }

    private function createService(string $name): Service
    {
        $externalIds = [
            'orders' => '0590139e-7481-466c-bcdf-929adcaaf804',
            'tracking' => '1590139e-7481-466c-bcdf-929adcaaf804',
        ];

        return Service::query()->create([
            'external_id' => $externalIds[$name] ?? '2590139e-7481-466c-bcdf-929adcaaf804',
            'name' => $name,
            'host' => "{$name}.example.com",
            'port' => 80,
            'protocol' => 'http',
        ]);
    }

    private function createRequestLog(
        Consumer $consumer,
        Service $service,
        int $lineNumber,
        int $requestLatency = 100,
        int $proxyLatency = 50,
        int $gatewayLatency = 10,
    ): RequestLog {
        return RequestLog::query()->create([
            'consumer_id' => $consumer->id,
            'service_id' => $service->id,
            'source_file_path' => 'logs.txt',
            'source_line_number' => $lineNumber,
            'method' => 'GET',
            'uri' => '/orders',
            'url' => 'http://orders.example.com/orders',
            'response_status' => 200,
            'proxy_latency' => $proxyLatency,
            'gateway_latency' => $gatewayLatency,
            'request_latency' => $requestLatency,
            'started_at' => 1433209822425,
            'processed_at' => now(),
        ]);
    }
}
