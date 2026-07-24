<?php

namespace App\Http\Requests\Products;

use App\Services\TempUploadService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['nullable', 'in:draft,published'],

            'wine_details' => ['sometimes', 'required', 'array'],
            'wine_details.year' => ['required_with:wine_details', 'integer', 'digits:4'],
            'wine_details.winery_guid' => ['required_with:wine_details', 'uuid', 'exists:wineries,guid'],
            'wine_details.grape_variety_guid' => ['required_with:wine_details', 'uuid', 'exists:grape_varieties,guid'],
            'wine_details.wine_region_guid' => ['required_with:wine_details', 'uuid', 'exists:wine_regions,guid'],

            'images' => ['sometimes', 'array', 'min:1'],
            'images.*.image_id' => ['nullable', 'integer', 'exists:product_images,id'],
            'images.*.token' => ['nullable', 'string'],
            'images.*.is_main' => ['required_with:images', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'El título es obligatorio.',
            'title.max' => 'El título no puede superar los 255 caracteres.',
            'status.in' => 'El estado debe ser borrador o publicado.',

            'wine_details.year.required_with' => 'El año es obligatorio.',
            'wine_details.year.digits' => 'El año debe tener 4 dígitos.',
            'wine_details.winery_guid.required_with' => 'La bodega es obligatoria.',
            'wine_details.winery_guid.exists' => 'La bodega seleccionada no existe.',
            'wine_details.grape_variety_guid.required_with' => 'La variedad de uva es obligatoria.',
            'wine_details.grape_variety_guid.exists' => 'La variedad de uva seleccionada no existe.',
            'wine_details.wine_region_guid.required_with' => 'La región vitivinícola es obligatoria.',
            'wine_details.wine_region_guid.exists' => 'La región vitivinícola seleccionada no existe.',

            'images.min' => 'Debe haber al menos una imagen.',
            'images.*.image_id.exists' => 'Una de las imágenes referenciadas no existe.',
            'images.*.is_main.required_with' => 'Debe indicar si la imagen es la principal.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $images = $this->input('images');

            if (! is_array($images) || empty($images)) {
                return;
            }

            foreach ($images as $index => $image) {
                $hasImageId = ! empty($image['image_id']);
                $hasToken = ! empty($image['token']);

                if ($hasImageId === $hasToken) {
                    $validator->errors()->add("images.{$index}", 'Cada imagen debe tener exactamente un identificador (image_id) o un token, no ambos.');

                    continue;
                }

                if ($hasToken) {
                    $tempUploadService = app(TempUploadService::class);
                    if (! $tempUploadService->isValidToken($image['token'])) {
                        $validator->errors()->add("images.{$index}.token", 'El token de la imagen no es válido.');
                    }
                }
            }

            $mainCount = collect($images)->filter(fn ($image) => (bool) ($image['is_main'] ?? false))->count();

            if ($mainCount !== 1) {
                $validator->errors()->add('images', 'Debe haber exactamente una imagen principal.');
            }
        });
    }
}
