<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTerapiaRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'id_medico' => 'required|exists:profesionals,id',
            'id_servicio' => 'required|exists:servicio,id',
            'historia.id_paciente' => 'required|exists:pacientes,id',
            'Terapia.objetivos' => 'required|string',
            'Terapia.fecha' => 'required|date',
            'Terapia.hora' => 'required|date_format:H:i',
            'Terapia.sesion' => 'nullable|integer|min:1',
            'Terapia.evolucion' => 'nullable|string',
            'Diagnosticos' => 'nullable|array',
            'DiagnosticosCIF' => 'nullable|array',
        ];
    }

    public function messages()
    {
        return [
            'id_medico.required' => 'El médico es obligatorio.',
            'id_servicio.required' => 'El servicio es obligatorio.',
            'historia.id_paciente.required' => 'El paciente es obligatorio.',
            'Terapia.objetivos.required' => 'Los objetivos de la terapia son obligatorios.',
            'Terapia.fecha.required' => 'La fecha de la terapia es obligatoria.',
            'Terapia.hora.required' => 'La hora de la terapia es obligatoria.',
        ];
    }
}
