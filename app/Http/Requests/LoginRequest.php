<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'correo' => 'required|email',
            'contraseña' => 'required|string',
        ];
    }

    public function messages()
    {
        return [
            'correo.required' => 'El correo es obligatorio.',
            'correo.email' => 'El correo debe ser un correo electrónico válido.',
            'contraseña.required' => 'La contraseña es obligatoria.',
        ];
    }
}
