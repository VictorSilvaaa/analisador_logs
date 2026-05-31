# Processamento e Captura de Erros

Este documento explica o fluxo usado para ler o arquivo de logs, processar os dados e registrar falhas.

## Visao geral

1. A rota `POST /api/logs/process` recebe a solicitacao.
2. O controller localiza o arquivo em `backend/storage/app/private/logs`.
3. Um Job principal e enviado para a fila.
4. O arquivo e aberto com `fopen` e lido linha a linha com `fgets`.
5. As linhas sao agrupadas em chunks de `500`.
6. Cada chunk e enviado para um Job proprio.
7. O Job do chunk valida, cria/atualiza dependencias e salva as requests.
8. Linhas invalidas ou erros de persistencia sao registrados em `log_processing_failures`.

## Arquivos principais

- `backend/app/Http/Controllers/LogFileProcessingController.php`: recebe a requisicao e valida o arquivo.
- `backend/app/Services/LogFileProcessingService.php`: dispara o Job principal.
- `backend/app/Jobs/ProcessLogFileJob.php`: inicia a leitura do arquivo.
- `backend/app/Services/LogFileStreamingService.php`: faz a leitura streaming com `fopen` e `fgets`.
- `backend/app/Jobs/ProcessLogFileChunkJob.php`: processa um chunk.
- `backend/app/Services/LogFileChunkProcessingService.php`: valida e persiste os dados do chunk.
- `backend/app/Repositories`: faz o acesso ao banco.
- `backend/app/Validators`: centraliza as regras de validacao.

## Leitura do arquivo

O arquivo precisa estar em:

```text
backend/storage/app/private/logs/logs.txt
```

A leitura nao carrega o arquivo inteiro em memoria. O service le uma linha por vez e monta chunks:

```text
LogFileStreamingService::CHUNK_SIZE = 400
```

Para mudar o tamanho do chunk, altere a constante `CHUNK_SIZE` em:

```text
backend/app/Services/LogFileStreamingService.php
```

## Formato esperado das linhas

Cada linha deve ser um JSON valido, no formato NDJSON: um JSON por linha.

Campos principais usados no processamento:

- `authenticated_entity.consumer_id.uuid`
- `service.id`
- `service.name`
- `service.host`
- `service.port`
- `service.protocol`
- `request.method`
- `request.uri`
- `request.url`
- `response.status`
- `latencies.proxy`
- `latencies.gateway`
- `latencies.request`
- `client_ip`
- `started_at`

## Validacoes

O processamento separa a validacao em tres grupos.

Consumer:

- `uuid` obrigatorio.
- `uuid` deve ser UUID valido.

Service:

- `external_id` obrigatorio e UUID valido.
- `host` obrigatorio.
- `port` obrigatorio entre `1` e `65535`.
- `protocol` obrigatorio.
- timeouts, retries e datas do service devem ser inteiros quando enviados.

Request:

- `consumer_id` e `service_id` obrigatorios e existentes no banco.
- `source_file_path` obrigatorio.
- `source_line_number` obrigatorio.
- `method`, `uri`, `url` obrigatorios.
- `response_status` obrigatorio entre `100` e `599`.
- latencias e tamanhos devem ser inteiros quando enviados.
- `client_ip` deve ser IP valido quando enviado.
- `started_at` obrigatorio e inteiro.

## Persistencia

O chunk primeiro persiste dependencias:

1. Agrupa consumers unicos pelo UUID.
2. Agrupa services unicos pelo `external_id`.
3. Faz upsert de consumers.
4. Faz upsert de services.
5. Busca os IDs internos criados ou ja existentes.

Depois monta as requests:

1. Confirma que `consumer_id` e `service_id` existem.
2. Valida os campos da request.
3. Tenta inserir em lote.
4. Se o lote falhar, tenta salvar linha por linha.

## Conversao de started_at

O campo `started_at` chega como timestamp em segundos ou milissegundos.

Antes de salvar, o sistema converte para data e hora no formato:

```text
Y-m-d H:i:s
```

Exemplo:

```text
1433209822425 -> 2015-06-02 01:50:22
```

## Captura de erros

Falhas de linha sao gravadas na tabela:

```text
log_processing_failures
```

Campos gravados:

| Campo | Descricao |
| --- | --- |
| `file_path` | Caminho do arquivo processado. |
| `line_number` | Numero da linha no arquivo. |
| `content` | Conteudo original da linha. |
| `error_message` | Mensagem do erro encontrado. |
| `context` | Dados extras sobre a etapa do erro. |
| `resolved_at` | Data em que a falha foi resolvida, quando aplicavel. |
| `resolved_message` | Mensagem informando como a falha foi resolvida. |

## Etapas registradas no contexto

`parse_ou_validacao_de_dependencias`:

- JSON invalido.
- Consumer invalido.
- Service invalido.

`validacao_da_request`:

- Request invalida.
- Consumer ou service nao encontrado apos persistencia.
- Relacionamento invalido.

`fallback_de_insert_da_request`:

- Insert em lote falhou.
- Linha tambem falhou ao ser salva individualmente.
- O contexto guarda tambem a mensagem do erro do lote.

## Fallback linha por linha

O insert em lote e a primeira opcao por performance.

Se ele falhar, o sistema processa cada request individualmente. Isso permite:

- salvar as linhas validas;
- registrar apenas as linhas invalidas;
- manter o processamento do restante do chunk.

Quando uma linha que estava marcada como falha e processada com sucesso em reprocessamento, o sistema marca a falha como resolvida.

## Logs simples do processamento

O arquivo de log operacional fica em:

```text
backend/storage/logs/log-file-processing.log
```

Ele registra:

- inicio do processamento;
- fim do processamento;
- erros internos capturados por `try/catch`.
