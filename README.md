# Analisador de Logs

Sistema backend para processar arquivos de logs NDJSON, persistir requisicoes em banco e gerar relatorios CSV.

## Menu principal

- [Funcionalidades](#funcionalidades)
- [Tecnologias](#tecnologias)
- [Requisitos](#requisitos)
- [Instalacao com Docker](#instalacao-com-docker)
- [Arquivo de logs](#arquivo-de-logs)
- [Processamento](#processamento)
- [Rotas principais](#rotas-principais)
- [Testes](#testes)
- [Saiba mais](#saiba-mais)

## Funcionalidades

- Processamento assincrono de arquivo de logs.
- Leitura streaming com `fopen` e `fgets`.
- Divisao do arquivo em chunks.
- Upsert de consumers e services.
- Insert em lote de requests com fallback linha por linha.
- Registro de falhas de processamento.
- Relatorios CSV por consumer, service e latencias.

## Tecnologias

- PHP 8.3
- Laravel
- MySQL 8
- Docker e Docker Compose
- Composer
- PHPUnit

## Requisitos

- Docker
- Docker Compose

## Instalacao com Docker

Na raiz do projeto:

Copie as variaveis de ambiente usadas pelo Docker:

```bash
cp .env.example .env
```

Copie as variaveis de ambiente usadas pelo Laravel:

```bash
cp backend/.env.example backend/.env
```

Suba os containers da API, banco e servicos auxiliares:

```bash
docker compose up -d
```

Gere a chave interna da aplicacao Laravel:

```bash
docker compose exec analisadorlogs_backend php artisan key:generate
```

Crie as tabelas no banco de dados:

```bash
docker compose exec analisadorlogs_backend php artisan migrate
```

A API fica disponivel em:

```text
http://localhost:8080/api
```

## Arquivo de logs

Crie a pasta:

```bash
mkdir -p backend/storage/app/private/logs
```

Mova o arquivo a ser lido para:

```text
analisador_logs\backend\storage\app\private\logs\logs.txt
```

## Processamento

Mantenha o worker da fila ativo com `queue:work`. Isso e importante porque o processamento roda em Jobs; sem o worker, a API apenas coloca os Jobs na fila e eles nao sao executados.

```bash
docker compose exec analisadorlogs_backend php artisan queue:work --tries=1 --timeout=0 --memory=512 --sleep=0
```

Parametros usados:

- `--tries=1`: evita reprocessar automaticamente um Job pesado que falhou.
- `--timeout=0`: evita timeout em arquivos grandes.
- `--memory=512`: aumenta o limite de memoria do worker para reduzir falhas por memoria durante chunks maiores.
- `--sleep=0`: faz o worker buscar novos Jobs sem pausa.

Inicie o processamento pela rota:

```text
POST /api/logs/process
```

Para processar outro arquivo dentro da pasta `logs`, envie o parametro:

```json
{
  "file_name": "outro-arquivo.txt"
}
```

## Rotas principais

```text
GET /api
POST /api/logs/process
GET /api/reports/requests-by-consumer
GET /api/reports/requests-by-service
GET /api/reports/average-latencies-by-service
```

## Testes

```bash
docker compose exec analisadorlogs_backend php artisan test
```

## Saiba mais

- [Rotas, metodos e parametros](docs/rotas.md)
- [Processamento dos logs e captura de erros](docs/processamento-e-erros.md)
