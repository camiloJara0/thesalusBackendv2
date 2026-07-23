<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SolicitarPermisoRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'id_profesional' => 'required|exists:profesionals,id',
            'id_seccion' => 'required|exists:secciones,id',
        ];
    }

    public function messages()
    {
        return [
            'id_profesional.required' => 'El profesional es obligatorio.',
            'id_profesional.exists' => 'El profesional seleccionado no es válido.',
            'id_seccion.required' => 'La sección es obligatoria.',
            'id_seccion.exists' => 'La sección seleccionada no es válida.',
        ];
    }
}
