<?php

namespace App\Http\Requests\Auctions;

use Illuminate\Foundation\Http\FormRequest;

class CreateAuctionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'status' => ['nullable', 'in:draft,scheduled,live,closed,cancelled'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'El título es obligatorio.',
            'title.max' => 'El título no puede superar los 255 caracteres.',
            'starts_at.required' => 'La fecha de inicio es obligatoria.',
            'starts_at.date' => 'La fecha de inicio no es válida.',
            'ends_at.date' => 'La fecha de fin no es válida.',
            'ends_at.after' => 'La fecha de fin debe ser posterior a la fecha de inicio.',
            'status.in' => 'El estado de la subasta no es válido.',
        ];
    }
}
