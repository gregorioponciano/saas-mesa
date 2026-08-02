<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class DeliveryAcceptInviteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'password' => 'required|string|min:6|confirmed',
            'cpf' => ['nullable', 'string', 'max:14', function (string $attribute, mixed $value, \Closure $fail): void {
                if (! isValidCpf($value)) {
                    $fail('O CPF informado é inválido.');
                }
            }],
            'cnh' => 'nullable|string|max:20',
            'vehicle_plate' => 'nullable|string|max:10|regex:/^[A-Z]{3}-\d{4}$/',
            'vehicle_model' => 'nullable|string|max:255',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
        ];
    }

    public function messages(): array
    {
        return [
            'password.required' => 'A senha é obrigatória.',
            'password.min' => 'A senha deve ter no mínimo 6 caracteres.',
            'password.confirmed' => 'A confirmação de senha não coincide.',
            'vehicle_plate.regex' => 'A placa deve estar no formato AAA-0000.',
            'avatar.image' => 'A foto deve ser uma imagem.',
            'avatar.mimes' => 'A foto deve ser JPEG, PNG ou WebP.',
            'avatar.max' => 'A foto deve ter no máximo 5MB.',
        ];
    }
}
