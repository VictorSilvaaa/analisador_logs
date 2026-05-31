<?php

namespace Tests\Unit\Repositories;

use App\DTOs\CreateConsumerData;
use App\Repositories\EloquentConsumerRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EloquentConsumerRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private EloquentConsumerRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new EloquentConsumerRepository();
    }

    // Cobre a criacao basica de um consumidor usando o DTO esperado pelo repository.
    public function test_create_persists_consumer(): void
    {
        $consumer = $this->repository->create(
            new CreateConsumerData('80f74eef-31b8-45d5-c525-ae532297ea8e')
        );

        $this->assertDatabaseHas('consumers', [
            'id' => $consumer->id,
            'uuid' => '80f74eef-31b8-45d5-c525-ae532297ea8e',
        ]);
    }

    // Cobre a busca pelo identificador externo que vem do arquivo de log.
    public function test_find_by_uuid_returns_consumer(): void
    {
        $created = $this->repository->create(
            new CreateConsumerData('80f74eef-31b8-45d5-c525-ae532297ea8e')
        );

        $found = $this->repository->findByUuid('80f74eef-31b8-45d5-c525-ae532297ea8e');

        $this->assertTrue($created->is($found));
    }

    // Cobre a decisao de nao duplicar consumers quando o mesmo uuid aparece novamente.
    public function test_first_or_create_reuses_existing_consumer(): void
    {
        $data = new CreateConsumerData('80f74eef-31b8-45d5-c525-ae532297ea8e');

        $first = $this->repository->firstOrCreate($data);
        $second = $this->repository->firstOrCreate($data);

        $this->assertTrue($first->is($second));
        $this->assertDatabaseCount('consumers', 1);
    }

    // Cobre o upsert em lote usado nos chunks para aceitar repeticao sem quebrar o processamento.
    public function test_upsert_many_inserts_unique_consumers(): void
    {
        $this->repository->upsertMany([
            ['uuid' => '80f74eef-31b8-45d5-c525-ae532297ea8e'],
            ['uuid' => '90f74eef-31b8-45d5-c525-ae532297ea8e'],
            ['uuid' => '80f74eef-31b8-45d5-c525-ae532297ea8e'],
        ]);

        $this->assertDatabaseCount('consumers', 2);
    }

    // Cobre o mapa uuid => id usado para relacionar requests depois de salvar dependencias.
    public function test_find_ids_by_uuids_returns_uuid_indexed_map(): void
    {
        $consumer = $this->repository->create(
            new CreateConsumerData('80f74eef-31b8-45d5-c525-ae532297ea8e')
        );

        $ids = $this->repository->findIdsByUuids([
            '80f74eef-31b8-45d5-c525-ae532297ea8e',
            'not-found',
        ]);

        $this->assertSame([
            '80f74eef-31b8-45d5-c525-ae532297ea8e' => $consumer->id,
        ], $ids);
    }
}
