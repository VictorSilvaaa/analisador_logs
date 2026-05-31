<?php

namespace App\Validators;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ServiceLogValidator
{
    /**
     * @throws ValidationException
     */
    public function validate(array $data): array
    {
        return Validator::make($data, [
            'external_id' => ['required', 'uuid'],
            'name' => ['nullable', 'string'],
            'host' => ['required', 'string'],
            'path' => ['nullable', 'string'],
            'port' => ['required', 'integer', 'min:1', 'max:65535'],
            'protocol' => ['required', 'string', 'max:20'],
            'connect_timeout' => ['nullable', 'integer', 'min:0'],
            'read_timeout' => ['nullable', 'integer', 'min:0'],
            'write_timeout' => ['nullable', 'integer', 'min:0'],
            'retries' => ['nullable', 'integer', 'min:0'],
            'service_created_at' => ['nullable', 'integer', 'min:0'],
            'service_updated_at' => ['nullable', 'integer', 'min:0'],
        ], $this->messages())->validate();
    }

    private function messages(): array
    {
        return [
            'required' => 'O campo :attribute e obrigatorio.',
            'uuid' => 'O campo :attribute deve ser um UUID valido.',
            'string' => 'O campo :attribute deve ser um texto.',
            'integer' => 'O campo :attribute deve ser um numero inteiro.',
            'min' => 'O campo :attribute possui valor abaixo do permitido.',
            'max' => 'O campo :attribute possui valor acima do permitido.',
        ];
    }
}
