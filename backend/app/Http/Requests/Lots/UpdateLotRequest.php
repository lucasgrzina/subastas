<?php

namespace App\Http\Requests\Lots;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'lot_number' => ['sometimes', 'required', 'string', 'max:50'],
            'title' => ['nullable', 'string', 'max:255'],
            'starting_price' => ['sometimes', 'required', 'numeric', 'gt:0', 'decimal:0,2'],
            'bid_increment' => ['sometimes', 'required', 'numeric', 'gt:0', 'decimal:0,2'],
            'reserve_price' => ['nullable', 'numeric', 'gt:0', 'decimal:0,2'],
            // 4-value set only (per tasks obs#41 1.2a) — validation allows the full
            // enum domain, but LotService::update() rejects a direct write into
            // sold/unsold (only close() may set those) and the reverse-transition
            // guard rejects any status change once the lot is already terminal.
            'status' => ['sometimes', 'in:scheduled,open,sold,unsold'],

            'products' => ['sometimes', 'array', 'min:1'],
            'products.*.product_guid' => ['required_with:products', 'uuid', 'exists:products,guid', 'distinct'],
            'products.*.quantity' => ['required_with:products', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'lot_number.required' => 'El número de lote es obligatorio.',
            'title.string' => 'El título debe ser un texto.',
            'title.max' => 'El título no puede superar los 255 caracteres.',
            'starting_price.gt' => 'El precio base debe ser mayor a cero.',
            'starting_price.decimal' => 'El precio base admite hasta 2 decimales.',
            'bid_increment.gt' => 'El incremento mínimo debe ser mayor a cero.',
            'bid_increment.decimal' => 'El incremento mínimo admite hasta 2 decimales.',
            'reserve_price.gt' => 'El precio de reserva debe ser mayor a cero.',
            'reserve_price.decimal' => 'El precio de reserva admite hasta 2 decimales.',
            'status.in' => 'El estado del lote no es válido.',

            'products.min' => 'El lote debe tener al menos un producto.',
            'products.*.product_guid.required_with' => 'Cada producto es obligatorio.',
            'products.*.product_guid.exists' => 'Uno de los productos seleccionados no existe.',
            'products.*.product_guid.distinct' => 'No se puede repetir el mismo producto en el lote.',
            'products.*.quantity.required_with' => 'La cantidad es obligatoria.',
            'products.*.quantity.min' => 'La cantidad debe ser al menos 1.',
        ];
    }
}
