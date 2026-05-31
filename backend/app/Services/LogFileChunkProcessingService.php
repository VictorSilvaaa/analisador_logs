<?php

namespace App\Services;

use App\DTOs\CreateLogProcessingFailureData;
use App\Repositories\Contracts\ConsumerRepositoryInterface;
use App\Repositories\Contracts\LogProcessingFailureRepositoryInterface;
use App\Repositories\Contracts\RequestLogRepositoryInterface;
use App\Repositories\Contracts\ServiceRepositoryInterface;
use App\Validators\ConsumerLogValidator;
use App\Validators\RequestLogValidator;
use App\Validators\ServiceLogValidator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class LogFileChunkProcessingService
{
    public function __construct(
        private readonly ConsumerRepositoryInterface $consumerRepository,
        private readonly ServiceRepositoryInterface $serviceRepository,
        private readonly RequestLogRepositoryInterface $requestLogRepository,
        private readonly LogProcessingFailureRepositoryInterface $failureRepository,
        private readonly ConsumerLogValidator $consumerValidator,
        private readonly ServiceLogValidator $serviceValidator,
        private readonly RequestLogValidator $requestValidator,
        private readonly LogFileProcessingLogger $processingLogger,
    ) {
    }

    public function process(string $filePath, array $lines): void
    {
        $entries = $this->prepareEntries($filePath, $lines);

        if ($entries === []) {
            return;
        }

        // Consumidores e servicos precisam existir antes das requisicoes
        [$consumerIdsByUuid, $serviceIdsByExternalId] = DB::transaction(
            fn (): array => $this->persistDependencies($entries)
        );

        $requestEntries = $this->buildRequestEntries(
            filePath: $filePath,
            entries: $entries,
            consumerIdsByUuid: $consumerIdsByUuid,
            serviceIdsByExternalId: $serviceIdsByExternalId,
        );

        if ($requestEntries === []) {
            return;
        }

        try {
            DB::transaction(function () use ($requestEntries): void {
                $this->requestLogRepository->insertMany(
                    array_column($requestEntries, 'data')
                );
            });

            $this->markEntriesResolved($filePath, $requestEntries);
        } catch (Throwable $exception) {
            $this->processingLogger->internalError($filePath, $exception);
            $this->fallbackLineByLine($filePath, $requestEntries, $exception);
        }
    }

    private function prepareEntries(string $filePath, array $lines): array
    {
        $entries = [];

        foreach ($lines as $line) {
            try {
                // A linha so segue para persistencia depois de JSON, consumidor e servico validos.
                $payload = json_decode($line['content'], true, 512, JSON_THROW_ON_ERROR);
                $consumerData = $this->extractConsumerData($payload);
                $serviceData = $this->extractServiceData($payload);

                $this->consumerValidator->validate($consumerData);
                $this->serviceValidator->validate($serviceData);

                $entries[] = [
                    'line_number' => $line['line_number'],
                    'content' => $line['content'],
                    'payload' => $payload,
                    'consumer' => $consumerData,
                    'service' => $serviceData,
                ];
            } catch (Throwable $exception) {
                $this->recordFailure(
                    filePath: $filePath,
                    lineNumber: $line['line_number'] ?? null,
                    content: $line['content'] ?? null,
                    errorMessage: $this->getExceptionMessage($exception),
                    context: ['etapa' => 'parse_ou_validacao_de_dependencias'],
                );
            }
        }

        return $entries;
    }

    private function persistDependencies(array $entries): array
    {
        $consumersByUuid = [];
        $servicesByExternalId = [];

        foreach ($entries as $entry) {
            $consumersByUuid[$entry['consumer']['uuid']] = $entry['consumer'];
            $servicesByExternalId[$entry['service']['external_id']] = $entry['service'];
        }

        // Upsert evita erro em reprocessamento e em chunks paralelos com a mesma dependencia.
        $this->consumerRepository->upsertMany(array_values($consumersByUuid));
        $this->serviceRepository->upsertMany(array_values($servicesByExternalId));

        return [
            $this->consumerRepository->findIdsByUuids(array_keys($consumersByUuid)),
            $this->serviceRepository->findIdsByExternalIds(array_keys($servicesByExternalId)),
        ];
    }

    private function buildRequestEntries(
        string $filePath,
        array $entries,
        array $consumerIdsByUuid,
        array $serviceIdsByExternalId
    ): array {
        $requestEntries = [];

        foreach ($entries as $entry) {
            try {
                $consumerUuid = $entry['consumer']['uuid'];
                $serviceExternalId = $entry['service']['external_id'];
                $consumerId = $consumerIdsByUuid[$consumerUuid] ?? null;
                $serviceId = $serviceIdsByExternalId[$serviceExternalId] ?? null;

                if ($consumerId === null || $serviceId === null) {
                    throw new RuntimeException('Consumidor ou servico nao encontrado apos persistencia.');
                }

                $requestData = $this->extractRequestData(
                    payload: $entry['payload'],
                    filePath: $filePath,
                    lineNumber: $entry['line_number'],
                    consumerId: $consumerId,
                    serviceId: $serviceId,
                );

                $requestEntries[] = [
                    'line_number' => $entry['line_number'],
                    'content' => $entry['content'],
                    'data' => $this->requestValidator->validate($requestData),
                ];
            } catch (Throwable $exception) {
                $this->recordFailure(
                    filePath: $filePath,
                    lineNumber: $entry['line_number'],
                    content: $entry['content'],
                    errorMessage: $this->getExceptionMessage($exception),
                    context: ['etapa' => 'validacao_da_request'],
                );
            }
        }

        return $requestEntries;
    }

    private function fallbackLineByLine(string $filePath, array $requestEntries, Throwable $bulkException): void
    {
        foreach ($requestEntries as $entry) {
            try {
                // Se o lote falhar, salva linha a linha para preservar as requisicoes validas.
                DB::transaction(function () use ($entry): void {
                    $this->requestLogRepository->createFromArray($entry['data']);
                });

                $this->failureRepository->markResolved(
                    $filePath,
                    $entry['line_number'],
                    'Processada com sucesso no reprocessamento.',
                );
            } catch (Throwable $exception) {
                $this->recordFailure(
                    filePath: $filePath,
                    lineNumber: $entry['line_number'],
                    content: $entry['content'],
                    errorMessage: $exception->getMessage(),
                    context: [
                        'etapa' => 'fallback_de_insert_da_request',
                        'erro_do_lote' => $bulkException->getMessage(),
                    ],
                );
            }
        }
    }

    private function extractConsumerData(array $payload): array
    {
        return [
            'uuid' => $payload['authenticated_entity']['consumer_id']['uuid'] ?? null,
        ];
    }

    private function extractServiceData(array $payload): array
    {
        $service = $payload['service'] ?? [];

        return [
            'external_id' => $service['id'] ?? null,
            'name' => $service['name'] ?? null,
            'host' => $service['host'] ?? null,
            'path' => $service['path'] ?? null,
            'port' => $service['port'] ?? null,
            'protocol' => $service['protocol'] ?? null,
            'connect_timeout' => $service['connect_timeout'] ?? null,
            'read_timeout' => $service['read_timeout'] ?? null,
            'write_timeout' => $service['write_timeout'] ?? null,
            'retries' => $service['retries'] ?? null,
            'service_created_at' => $service['created_at'] ?? null,
            'service_updated_at' => $service['updated_at'] ?? null,
        ];
    }

    private function extractRequestData(array $payload, string $filePath, int $lineNumber, int $consumerId, int $serviceId): array
    {
        return [
            'consumer_id' => $consumerId,
            'service_id' => $serviceId,
            'source_file_path' => $filePath,
            'source_line_number' => $lineNumber,
            'method' => $payload['request']['method'] ?? null,
            'uri' => $payload['request']['uri'] ?? null,
            'url' => $payload['request']['url'] ?? null,
            'request_size' => $payload['request']['size'] ?? null,
            'upstream_uri' => $payload['upstream_uri'] ?? null,
            'response_status' => $payload['response']['status'] ?? null,
            'response_size' => $payload['response']['size'] ?? null,
            'proxy_latency' => $payload['latencies']['proxy'] ?? null,
            'gateway_latency' => $payload['latencies']['gateway'] ?? null,
            'request_latency' => $payload['latencies']['request'] ?? null,
            'client_ip' => $payload['client_ip'] ?? null,
            'started_at' => $payload['started_at'] ?? null,
            'request_headers' => $payload['request']['headers'] ?? null,
            'response_headers' => $payload['response']['headers'] ?? null,
            'querystring' => $payload['request']['querystring'] ?? null,
        ];
    }

    private function markEntriesResolved(string $filePath, array $requestEntries): void
    {
        foreach ($requestEntries as $entry) {
            $this->failureRepository->markResolved(
                $filePath,
                $entry['line_number'],
                'Processada com sucesso no reprocessamento.',
            );
        }
    }

    private function recordFailure(
        string $filePath,
        ?int $lineNumber,
        ?string $content,
        string $errorMessage,
        ?array $context = null
    ): void {
        $this->failureRepository->create(
            new CreateLogProcessingFailureData(
                filePath: $filePath,
                lineNumber: $lineNumber,
                content: $content,
                errorMessage: $errorMessage,
                context: $context,
            )
        );
    }

    private function getExceptionMessage(Throwable $exception): string
    {
        if ($exception instanceof ValidationException) {
            return $exception->validator->errors()->first() ?: $exception->getMessage();
        }

        return $exception->getMessage();
    }
}
