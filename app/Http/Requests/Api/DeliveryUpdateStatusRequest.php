<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class DeliveryUpdateStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => 'required|in:entregue,cancelado',
            'photo' => 'nullable|image|max:5120',
            'lat' => 'nullable|numeric|between:-90,90',
            'lng' => 'nullable|numeric|between:-180,180',
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'O status é obrigatório.',
            'status.in' => 'Status deve ser entregue ou cancelado.',
            'photo.image' => 'O comprovante deve ser uma imagem.',
            'photo.max' => 'A imagem deve ter no máximo 5MB.',
        ];
    }
}
