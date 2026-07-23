<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConsumirPermisoRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'permiso_id' => 'required|exists:profesional_has_permisos,id',
        ];
    }

    public function messages()
    {
        return [
            'permiso_id.required' => 'El ID del permiso es obligatorio.',
            'permiso_id.exists' => 'El permiso no existe.',
        ];
    }
}
