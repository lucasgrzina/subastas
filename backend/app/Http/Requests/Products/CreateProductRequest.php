<?php

namespace App\Http\Requests\Products;

use App\Services\TempUploadService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class CreateProductRequest extends FormRequest
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
            'status' => ['nullable', 'in:draft,published'],

            'wine_details' => ['required', 'array'],
            'wine_details.year' => ['required', 'integer', 'digits:4'],
            'wine_details.winery_guid' => ['required', 'uuid', 'exists:wineries,guid'],
            'wine_details.grape_variety_guid' => ['required', 'uuid', 'exists:grape_varieties,guid'],
            'wine_details.wine_region_guid' => ['required', 'uuid', 'exists:wine_regions,guid'],

            'images' => ['required', 'array', 'min:1'],
            'images.*.token' => ['required', 'string'],
            'images.*.is_main' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'El título es obligatorio.',
            'title.max' => 'El título no puede superar los 255 caracteres.',
            'status.in' => 'El estado debe ser borrador o publicado.',

            'wine_details.required' => 'Los datos del vino son obligatorios.',
            'wine_details.year.required' => 'El año es obligatorio.',
            'wine_details.year.digits' => 'El año debe tener 4 dígitos.',
            'wine_details.winery_guid.required' => 'La bodega es obligatoria.',
            'wine_details.winery_guid.exists' => 'La bodega seleccionada no existe.',
            'wine_details.grape_variety_guid.required' => 'La variedad de uva es obligatoria.',
            'wine_details.grape_variety_guid.exists' => 'La variedad de uva seleccionada no existe.',
            'wine_details.wine_region_guid.required' => 'La región vitivinícola es obligatoria.',
            'wine_details.wine_region_guid.exists' => 'La región vitivinícola seleccionada no existe.',

            'images.required' => 'Debe cargar al menos una imagen.',
            'images.min' => 'Debe cargar al menos una imagen.',
            'images.*.token.required' => 'Cada imagen debe tener un token válido.',
            'images.*.is_main.required' => 'Debe indicar si la imagen es la principal.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $images = $this->input('images', []);

            if (! is_array($images) || empty($images)) {
                return;
            }

            $mainCount = collect($images)->filter(fn ($image) => (bool) ($image['is_main'] ?? false))->count();

            if ($mainCount !== 1) {
                $validator->errors()->add('images', 'Debe haber exactamente una imagen principal.');
            }

            $tempUploadService = app(TempUploadService::class);
            foreach ($images as $index => $image) {
                $token = $image['token'] ?? null;
                if ($token && ! $tempUploadService->isValidToken($token)) {
                    $validator->errors()->add("images.{$index}.token", 'El token de la imagen no es válido.');
                }
            }
        });
    }
}
