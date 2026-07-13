<?php

namespace App\Http\Requests\Currencies;

use Illuminate\Foundation\Http\FormRequest;

class CreateCurrencyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string'],
            'name' => ['required', 'string'],
            'symbol' => ['required', 'string'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'El código es obligatorio.',
            'name.required' => 'El nombre es obligatorio.',
            'symbol.required' => 'El símbolo es obligatorio.',
            'is_active.required' => 'Debés indicar si la moneda está activa.',
        ];
    }
}
