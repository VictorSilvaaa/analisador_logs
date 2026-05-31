<?php

namespace App\Validators;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ConsumerLogValidator
{
    /**
     * @throws ValidationException
     */
    public function validate(array $data): array
    {
        return Validator::make($data, [
            'uuid' => ['required', 'uuid'],
        ], $this->messages())->validate();
    }

    private function messages(): array
    {
        return [
            'required' => 'O campo :attribute e obrigatorio.',
            'uuid' => 'O campo :attribute deve ser um UUID valido.',
        ];
    }
}
