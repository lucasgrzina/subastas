<?php

namespace App\Http\Requests\Uploads;

use Illuminate\Foundation\Http\FormRequest;

class StoreTempImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $maxKb = (int) config('uploads.max_kb', 4096);

        return [
            // Defensa en profundidad: image (getimagesize) + mimetypes (mime real).
            // SVG excluido deliberadamente (vector de XSS).
            'image' => [
                'required',
                'file',
                'image',
                'mimetypes:image/jpeg,image/png,image/webp',
                "max:{$maxKb}",
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'image.required' => 'Debe seleccionar una imagen.',
            'image.image' => 'El archivo debe ser una imagen válida.',
            'image.mimetypes' => 'La imagen debe ser JPEG, PNG o WebP.',
            'image.max' => 'La imagen supera el tamaño máximo permitido.',
        ];
    }
}
