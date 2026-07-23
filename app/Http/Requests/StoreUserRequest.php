<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'No_document' => 'required|string|max:20',
            'type_doc' => 'required|string|max:10',
            'celular' => 'required|string|max:20',
            'telefono' => 'nullable|string|max:20',
            'nacimiento' => 'required|date',
            'direccion' => 'required|string|max:255',
            'municipio' => 'required|string|max:100',
            'departamento' => 'required|string|max:100',
            'barrio' => 'required|string|max:100',
            'zona' => 'required|string|max:50',
            'correo' => 'required|email|max:255|unique:users,correo',
            'contraseña' => 'required|min:6|max:255',
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
            'nacimiento.date' => 'La fecha de nacimiento debe ser una fecha válida.',
            'direccion.required' => 'La dirección es obligatoria.',
            'municipio.required' => 'El municipio es obligatorio.',
            'departamento.required' => 'El departamento es obligatorio.',
            'barrio.required' => 'El barrio es obligatorio.',
            'zona.required' => 'La zona es obligatoria.',
            'correo.required' => 'El correo es obligatorio.',
            'correo.email' => 'El correo debe ser un correo electrónico válido.',
            'correo.unique' => 'El correo ya está registrado.',
            'contraseña.required' => 'La contraseña es obligatoria.',
            'contraseña.min' => 'La contraseña debe tener al menos 6 caracteres.',
        ];
    }
}
