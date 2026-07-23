<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCitaRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'id' => 'required|exists:citas,id',
            'id_servicio' => 'required|exists:servicio,id',
            'motivo' => 'required|string|max:500',
            'fecha' => 'required|date',
            'fechaHasta' => 'nullable|date|after_or_equal:fecha',
            'hora' => 'required|date_format:H:i',
            'motivo_edicion' => 'nullable|string|max:500',
        ];
    }

    public function messages()
    {
        return [
            'id.required' => 'El ID de la cita es obligatorio.',
            'id.exists' => 'La cita no existe.',
            'id_servicio.required' => 'El servicio es obligatorio.',
            'motivo.required' => 'El motivo es obligatorio.',
            'fecha.required' => 'La fecha es obligatoria.',
            'hora.required' => 'La hora es obligatoria.',
        ];
    }
}
