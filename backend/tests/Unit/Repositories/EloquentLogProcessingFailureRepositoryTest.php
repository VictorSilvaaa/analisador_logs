<?php

namespace Tests\Unit\Repositories;

use App\DTOs\CreateLogProcessingFailureData;
use App\Models\LogProcessingFailure;
use App\Repositories\EloquentLogProcessingFailureRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EloquentLogProcessingFailureRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private EloquentLogProcessingFailureRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new EloquentLogProcessingFailureRepository();
    }

    // Cobre o registro de uma linha invalida com contexto para investigacao posterior.
    public function test_create_persists_processing_failure(): void
    {
        $failure = $this->repository->create(new CreateLogProcessingFailureData(
            filePath: 'logs/import.txt',
            lineNumber: 10,
            content: '{"invalid": true}',
            errorMessage: 'Consumer invalido.',
            context: ['etapa' => 'validacao'],
        ));

        $this->assertDatabaseHas('log_processing_failures', [
            'id' => $failure->id,
            'file_path' => 'logs/import.txt',
            'line_number' => 10,
            'error_message' => 'Consumer invalido.',
        ]);
        $this->assertSame(['etapa' => 'validacao'], $failure->refresh()->context);
    }

    // Cobre a decisao de resolver apenas a falha pendente da mesma linha do mesmo arquivo.
    public function test_mark_resolved_updates_only_matching_unresolved_failure(): void
    {
        $target = LogProcessingFailure::query()->create([
            'file_path' => 'logs/import.txt',
            'line_number' => 10,
            'content' => 'line',
            'error_message' => 'Erro original.',
        ]);
        $otherLine = LogProcessingFailure::query()->create([
            'file_path' => 'logs/import.txt',
            'line_number' => 11,
            'content' => 'line',
            'error_message' => 'Outro erro.',
        ]);
        $alreadyResolved = LogProcessingFailure::query()->create([
            'file_path' => 'logs/import.txt',
            'line_number' => 10,
            'content' => 'line',
            'error_message' => 'Erro antigo.',
            'resolved_at' => now()->subDay(),
            'resolved_message' => 'Resolvido antes.',
        ]);

        $this->repository->markResolved('logs/import.txt', 10, 'Processada depois.');

        $this->assertNotNull($target->refresh()->resolved_at);
        $this->assertSame('Processada depois.', $target->resolved_message);
        $this->assertNull($otherLine->refresh()->resolved_at);
        $this->assertSame('Resolvido antes.', $alreadyResolved->refresh()->resolved_message);
    }
}
