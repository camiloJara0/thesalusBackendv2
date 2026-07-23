<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VerificarPermisosRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'id_profesional' => 'required|exists:profesionals,id',
        ];
    }

    public function messages()
    {
        return [
            'id_profesional.required' => 'El profesional es obligatorio.',
            'id_profesional.exists' => 'El profesional seleccionado no es válido.',
        ];
    }
}
