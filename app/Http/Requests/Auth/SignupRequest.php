<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class SignupRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'name' => ['required', 'string'],
            'email' => ['required', 'email', 'unique:users_sbe,email'], // Ojo: tabla users_sbe
            'password' => ['required', Password::min(10)->numbers()],

            // Campos que existen en cpu_personas
            'cedula' => ['required', 'string'],
            'nombres' => ['required', 'string'],
            'direccion' => ['required', 'string'],
            'celular' => ['nullable', 'string'],
            'fechanaci' => ['nullable', 'date'],
            'sexo' => ['required', 'string'],
            'estado_civil' => ['required', 'string'],
            'nacionalidad' => ['required', 'string'],
            'provincia' => ['required', 'string'],
            'ciudad' => ['required', 'string'],
            'parroquia' => ['nullable', 'string'],
            'tipoetnia' => ['required', 'string'],
            'discapacidad' => ['nullable', 'string'],
            'id_tipo_usuario' => ['required', 'integer'],
        ];
    }
}
