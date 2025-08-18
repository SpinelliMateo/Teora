<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InyectadoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'numero_serie' => 'required|string|max:255'
        ];
    }

    public function messages(): array
    {
        return [
            'numero_serie.required' => 'El número de serie es obligatorio.',
            'numero_serie.string' => 'El número de serie debe ser una cadena de texto.',
            'numero_serie.max' => 'El número de serie no puede exceder 255 caracteres.'
        ];
    }
}