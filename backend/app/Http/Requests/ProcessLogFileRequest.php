<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProcessLogFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file_name' => [
                'sometimes',
                'nullable',
                'string',
                function (string $attribute, mixed $value, callable $fail): void {
                    if ($value === null || $value === '') {
                        return;
                    }

                    if (str_contains($value, '/') || str_contains($value, '\\')) {
                        $fail('Informe apenas o nome do arquivo de logs.');
                    }
                },
            ],
        ];
    }
}
