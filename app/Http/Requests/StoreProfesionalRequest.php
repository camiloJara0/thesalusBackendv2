<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProfesionalRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'No_document' => 'required|integer',
            'type_doc' => 'required|string|max:10',
            'celular' => 'required|integer',
            'telefono' => 'nullable|integer',
            'nacimiento' => 'required|date',
            'direccion' => 'required|string|max:255',
            'municipio' => 'required|string|max:100',
            'departamento' => 'required|string|max:100',
            'barrio' => 'required|string|max:100',
            'zona' => 'required|string|max:50',
            'correo' => 'required|email|max:255|unique:users,correo',
            'id_profesion' => 'required|exists:profesions,id',
            'zona_laboral' => 'required|string|max:100',
            'departamento_laboral' => 'required|string|max:100',
            'municipio_laboral' => 'required|string|max:100',
            'selloFile' => 'nullable|file|mimes:png,jpg,jpeg,webp|max:5120',
            'id_correoCreador' => 'required|string',
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'No_document.required' => 'El número de documento es obligatorio.',
            'type_doc.required' => 'El tipo de documento es obligatorio.',
            'celular.required' => 'El celular es obligatorio.',
            'nacimiento.required' => 'La fecha de nacimiento es obligatoria.',
            'direccion.required' => 'La dirección es obligatoria.',
            'municipio.required' => 'El municipio es obligatorio.',
            'departamento.required' => 'El departamento es obligatorio.',
            'barrio.required' => 'El barrio es obligatorio.',
            'zona.required' => 'La zona es obligatoria.',
            'correo.required' => 'El correo es obligatorio.',
            'correo.email' => 'El correo debe ser un correo electrónico válido.',
            'correo.unique' => 'El correo ya está registrado.',
            'id_profesion.required' => 'La profesión es obligatoria.',
            'id_profesion.exists' => 'La profesión seleccionada no es válida.',
            'zona_laboral.required' => 'La zona laboral es obligatoria.',
            'departamento_laboral.required' => 'El departamento laboral es obligatorio.',
            'municipio_laboral.required' => 'El municipio laboral es obligatorio.',
            'selloFile.mimes' => 'El sello debe ser una imagen (png, jpg, jpeg, webp).',
            'selloFile.max' => 'El sello no debe superar los 5MB.',
            'id_correoCreador.required' => 'El ID del creador es obligatorio.',
        ];
    }
}
