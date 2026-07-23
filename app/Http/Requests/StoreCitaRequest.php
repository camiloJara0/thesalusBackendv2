<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCitaRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'id_paciente' => 'required|exists:pacientes,id',
            'id_medico' => 'required|exists:profesionals,id',
            'id_servicio' => 'required|exists:servicio,id',
            'motivo' => 'required|string|max:500',
            'fecha' => 'required|date',
            'fechaHasta' => 'nullable|date|after_or_equal:fecha',
            'hora' => 'nullable|date_format:H:i',
            'procedimiento' => 'nullable|string|max:255',
            'codigo' => 'nullable|string|max:50',
            'dias_asignados' => 'nullable|integer|min:1',
            'id_procedimiento' => 'nullable|integer',
        ];
    }

    public function messages()
    {
        return [
            'id_paciente.required' => 'El paciente es obligatorio.',
            'id_paciente.exists' => 'El paciente seleccionado no es válido.',
            'id_medico.required' => 'El médico es obligatorio.',
            'id_medico.exists' => 'El médico seleccionado no es válido.',
            'id_servicio.required' => 'El servicio es obligatorio.',
            'id_servicio.exists' => 'El servicio seleccionado no es válido.',
            'motivo.required' => 'El motivo es obligatorio.',
            'fecha.required' => 'La fecha es obligatoria.',
            'fecha.date' => 'La fecha debe ser una fecha válida.',
        ];
    }
}
