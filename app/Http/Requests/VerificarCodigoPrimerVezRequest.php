<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VerificarCodigoPrimerVezRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'codigo' => 'required|string|max:10',
            'contraseña' => 'required|min:6',
        ];
    }

    public function messages()
    {
        return [
            'codigo.required' => 'El código es obligatorio.',
            'contraseña.required' => 'La contraseña es obligatoria.',
            'contraseña.min' => 'La contraseña debe tener al menos 6 caracteres.',
        ];
    }
}
