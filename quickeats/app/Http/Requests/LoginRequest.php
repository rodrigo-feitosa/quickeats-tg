<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => 'required|string|email|max:255|exists:users,email',
            'password' => 'required|string|min:8',
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'O campo de email é obrigatório.',
            'email.email' => 'O campo de email deve ser um endereço de email válido.',
            'email.max' => 'O campo de email não pode ter mais de 255 caracteres.',
            'password.required' => 'O campo de senha é obrigatório.',
            'password.min' => 'O campo de senha deve ter pelo menos 8 caracteres.',
            'email.exists' => 'O email ou senha inválidos.',
        ];
    }
}
