<?php

namespace Tests\Unit\Repositories;

use App\DTOs\CreateServiceData;
use App\Repositories\EloquentServiceRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EloquentServiceRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private EloquentServiceRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new EloquentServiceRepository();
    }

    // Cobre a criacao basica de um service com os dados principais do log.
    public function test_create_persists_service(): void
    {
        $service = $this->repository->create($this->serviceData(name: 'orders'));

        $this->assertDatabaseHas('services', [
            'id' => $service->id,
            'external_id' => '0590139e-7481-466c-bcdf-929adcaaf804',
            'name' => 'orders',
            'host' => 'orders.example.com',
        ]);
    }

    // Cobre a busca pelo id externo recebido no arquivo de log.
    public function test_find_by_external_id_returns_service(): void
    {
        $created = $this->repository->create($this->serviceData());

        $found = $this->repository->findByExternalId('0590139e-7481-466c-bcdf-929adcaaf804');

        $this->assertTrue($created->is($found));
    }

    // Cobre a decisao de atualizar o service quando o mesmo external_id reaparece com dados novos.
    public function test_update_or_create_updates_existing_service(): void
    {
        $this->repository->create($this->serviceData(name: 'old-name'));

        $service = $this->repository->updateOrCreate($this->serviceData(name: 'new-name'));

        $this->assertSame('new-name', $service->name);
        $this->assertDatabaseCount('services', 1);
    }

    // Cobre o upsert em lote usado pelos chunks e garante que campos mutaveis sejam atualizados.
    public function test_upsert_many_inserts_and_updates_services(): void
    {
        $this->repository->upsertMany([
            $this->serviceRow(name: 'orders'),
        ]);

        $this->repository->upsertMany([
            $this->serviceRow(name: 'orders-api', host: 'api.example.com'),
            $this->serviceRow(
                externalId: '1590139e-7481-466c-bcdf-929adcaaf804',
                name: 'tracking',
                host: 'tracking.example.com'
            ),
        ]);

        $this->assertDatabaseCount('services', 2);
        $this->assertDatabaseHas('services', [
            'external_id' => '0590139e-7481-466c-bcdf-929adcaaf804',
            'name' => 'orders-api',
            'host' => 'api.example.com',
        ]);
    }

    // Cobre o mapa external_id => id usado para montar as requests depois das dependencias.
    public function test_find_ids_by_external_ids_returns_external_id_indexed_map(): void
    {
        $service = $this->repository->create($this->serviceData());

        $ids = $this->repository->findIdsByExternalIds([
            '0590139e-7481-466c-bcdf-929adcaaf804',
            'not-found',
        ]);

        $this->assertSame([
            '0590139e-7481-466c-bcdf-929adcaaf804' => $service->id,
        ], $ids);
    }

    private function serviceData(string $name = 'orders'): CreateServiceData
    {
        return new CreateServiceData(
            externalId: '0590139e-7481-466c-bcdf-929adcaaf804',
            host: 'orders.example.com',
            port: 80,
            protocol: 'http',
            name: $name,
            path: '/orders',
            connectTimeout: 60000,
            readTimeout: 60000,
            writeTimeout: 60000,
            retries: 5,
            serviceCreatedAt: 1544800610,
            serviceUpdatedAt: 1544800611,
        );
    }

    private function serviceRow(
        string $externalId = '0590139e-7481-466c-bcdf-929adcaaf804',
        string $name = 'orders',
        string $host = 'orders.example.com'
    ): array {
        return [
            'external_id' => $externalId,
            'name' => $name,
            'host' => $host,
            'path' => '/orders',
            'port' => 80,
            'protocol' => 'http',
            'connect_timeout' => 60000,
            'read_timeout' => 60000,
            'write_timeout' => 60000,
            'retries' => 5,
            'service_created_at' => 1544800610,
            'service_updated_at' => 1544800611,
        ];
    }
}
