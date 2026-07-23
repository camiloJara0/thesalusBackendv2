<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePermisosRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'id_profesional' => 'required|exists:profesionals,id',
            'permisos' => 'required|array|min:1',
            'permisos.*' => 'string|max:100',
            'fecha_fin' => 'nullable|date|after:now',
        ];
    }

    public function messages()
    {
        return [
            'id_profesional.required' => 'El profesional es obligatorio.',
            'id_profesional.exists' => 'El profesional seleccionado no es válido.',
            'permisos.required' => 'Los permisos son obligatorios.',
            'permisos.array' => 'Los permisos deben ser un arreglo.',
            'permisos.min' => 'Debe seleccionar al menos un permiso.',
        ];
    }
}
