<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTerapiaRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'id' => 'required|exists:terapia,id',
            'objetivos' => 'required|string',
            'fecha' => 'required|date',
            'hora' => 'required|date_format:H:i',
            'sesion' => 'nullable|integer|min:1',
            'evolucion' => 'nullable|string',
        ];
    }

    public function messages()
    {
        return [
            'id.required' => 'El ID de la terapia es obligatorio.',
            'id.exists' => 'La terapia no existe.',
            'objetivos.required' => 'Los objetivos son obligatorios.',
            'fecha.required' => 'La fecha es obligatoria.',
            'hora.required' => 'La hora es obligatoria.',
        ];
    }
}
