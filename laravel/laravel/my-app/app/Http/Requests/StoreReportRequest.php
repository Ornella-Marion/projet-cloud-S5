<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authentification gérée par middleware auth:sanctum
    }

    public function rules(): array
    {
        return [
            'target_type'  => 'required|string|max:50',
            'report_date'  => 'required|date',
            'reason'       => 'required|string',
            'road_id'      => 'nullable|integer|exists:roads,id',
            'photo'        => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120', // 5 Mo max
        ];
    }

    public function messages(): array
    {
        return [
            'target_type.required'  => 'Le type de cible est obligatoire.',
            'target_type.max'       => 'Le type de cible ne doit pas dépasser 50 caractères.',
            'report_date.required'  => 'La date du signalement est obligatoire.',
            'report_date.date'      => 'La date du signalement doit être une date valide.',
            'reason.required'       => 'La raison du signalement est obligatoire.',
            'road_id.exists'        => 'La route sélectionnée est introuvable.',
            'photo.image'           => 'Le fichier doit être une image.',
            'photo.mimes'           => 'L\'image doit être au format jpeg, png, jpg ou gif.',
            'photo.max'             => 'L\'image ne doit pas dépasser 5 Mo.',
        ];
    }
}
