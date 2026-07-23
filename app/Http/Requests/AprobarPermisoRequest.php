<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AprobarPermisoRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'token' => 'required|string',
        ];
    }

    public function messages()
    {
        return [
            'token.required' => 'El token de aprobación es obligatorio.',
        ];
    }
}
