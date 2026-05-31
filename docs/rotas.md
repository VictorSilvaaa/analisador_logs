# Rotas da API

## GET /

Verifica se a API esta respondendo.

Resposta esperada:

```json
{
  "message": "Bem-vindo a API"
}
```

## POST /logs/process

Inicia o processamento assincrono de um arquivo de logs.

O arquivo deve estar dentro de:

```text
backend/storage/app/private/logs
```

### Sem parametros

Quando nenhum parametro e enviado, a API procura o arquivo padrao `logs.txt`.

Arquivo esperado:

```text
backend/storage/app/private/logs/logs.txt
```

Resposta de sucesso:

```json
{
  "message": "Processamento do arquivo de logs iniciado com sucesso."
}
```

### Com parametro file_name

Use `file_name` para processar outro arquivo dentro da pasta `logs`.

Exemplo de body:

```json
{
  "file_name": "meu-arquivo.txt"
}
```

Parametros aceitos:

| Campo | Tipo | Obrigatorio | Descricao |
| --- | --- | --- | --- |
| `file_name` | string | nao | Nome do arquivo dentro de `storage/app/private/logs`. Deve conter apenas o nome, sem `/` ou `\`. |

## GET /reports/requests-by-consumer

Gera CSV com o total de requests agrupado por consumer.

Colunas do CSV:

```text
consumer_id,total_requests
```

O download usa nome com timestamp:

```text
YYYYMMDD_HHMMSS_total-requests-by-consumer.csv
```

## GET /reports/requests-by-service

Gera CSV com o total de requests agrupado por service.

Colunas do CSV:

```text
service_name,total_requests
```

O download usa nome com timestamp:

```text
YYYYMMDD_HHMMSS_total-requests-by-service.csv
```

## GET /reports/average-latencies-by-service

Gera CSV com medias de latencias agrupadas por service.

Colunas do CSV:

```text
service_name,average_request_latency,average_proxy_latency,average_gateway_latency
```
