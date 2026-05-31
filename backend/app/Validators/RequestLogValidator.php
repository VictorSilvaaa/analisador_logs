<?php

namespace App\Validators;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class RequestLogValidator
{
    /**
     * @throws ValidationException
     */
    public function validate(array $data): array
    {
        return Validator::make($data, [
            'consumer_id' => ['required', 'integer', 'exists:consumers,id'],
            'service_id' => ['required', 'integer', 'exists:services,id'],
            'source_file_path' => ['required', 'string', 'max:512'],
            'source_line_number' => ['required', 'integer', 'min:1'],
            'method' => ['required', 'string', 'max:20'],
            'uri' => ['required', 'string'],
            'url' => ['required', 'string'],
            'request_size' => ['nullable', 'integer', 'min:0'],
            'upstream_uri' => ['nullable', 'string'],
            'response_status' => ['required', 'integer', 'min:100', 'max:599'],
            'response_size' => ['nullable', 'integer', 'min:0'],
            'proxy_latency' => ['nullable', 'integer', 'min:0'],
            'gateway_latency' => ['nullable', 'integer', 'min:0'],
            'request_latency' => ['nullable', 'integer', 'min:0'],
            'client_ip' => ['nullable', 'ip'],
            'started_at' => ['required', 'integer', 'min:0'],
            'request_headers' => ['nullable', 'array'],
            'response_headers' => ['nullable', 'array'],
            'querystring' => ['nullable', 'array'],
        ], $this->messages())->validate();
    }

    private function messages(): array
    {
        return [
            'required' => 'O campo :attribute e obrigatorio.',
            'integer' => 'O campo :attribute deve ser um numero inteiro.',
            'exists' => 'O campo :attribute referencia um registro inexistente.',
            'string' => 'O campo :attribute deve ser um texto.',
            'min' => 'O campo :attribute possui valor abaixo do permitido.',
            'max' => 'O campo :attribute possui valor acima do permitido.',
            'ip' => 'O campo :attribute deve ser um IP valido.',
            'array' => 'O campo :attribute deve ser uma lista de dados.',
        ];
    }
}
