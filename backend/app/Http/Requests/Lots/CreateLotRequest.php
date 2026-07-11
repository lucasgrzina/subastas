<?php

namespace App\Http\Requests\Lots;

use Illuminate\Foundation\Http\FormRequest;

class CreateLotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'auction_guid' => ['required', 'uuid', 'exists:auctions,guid'],
            'lot_number' => ['required', 'string', 'max:50'],
            'starting_price' => ['required', 'numeric', 'gt:0', 'decimal:0,2'],
            'bid_increment' => ['required', 'numeric', 'gt:0', 'decimal:0,2'],
            'reserve_price' => ['nullable', 'numeric', 'gt:0', 'decimal:0,2'],
            'status' => ['nullable', 'in:scheduled,open,sold,unsold'],

            'products' => ['required', 'array', 'min:1'],
            'products.*.product_guid' => ['required', 'uuid', 'exists:products,guid', 'distinct'],
            'products.*.quantity' => ['required', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'auction_guid.required' => 'La subasta es obligatoria.',
            'auction_guid.exists' => 'La subasta seleccionada no existe.',
            'lot_number.required' => 'El número de lote es obligatorio.',
            'starting_price.required' => 'El precio base es obligatorio.',
            'starting_price.gt' => 'El precio base debe ser mayor a cero.',
            'starting_price.decimal' => 'El precio base admite hasta 2 decimales.',
            'bid_increment.required' => 'El incremento mínimo es obligatorio.',
            'bid_increment.gt' => 'El incremento mínimo debe ser mayor a cero.',
            'bid_increment.decimal' => 'El incremento mínimo admite hasta 2 decimales.',
            'reserve_price.gt' => 'El precio de reserva debe ser mayor a cero.',
            'reserve_price.decimal' => 'El precio de reserva admite hasta 2 decimales.',
            'status.in' => 'El estado del lote no es válido.',

            'products.required' => 'El lote debe tener al menos un producto.',
            'products.min' => 'El lote debe tener al menos un producto.',
            'products.*.product_guid.required' => 'Cada producto es obligatorio.',
            'products.*.product_guid.exists' => 'Uno de los productos seleccionados no existe.',
            'products.*.product_guid.distinct' => 'No se puede repetir el mismo producto en el lote.',
            'products.*.quantity.required' => 'La cantidad es obligatoria.',
            'products.*.quantity.min' => 'La cantidad debe ser al menos 1.',
        ];
    }
}
