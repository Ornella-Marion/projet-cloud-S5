<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Seul un manager peut modifier un signalement
        return $this->user() && $this->user()->role === 'manager';
    }

    public function rules(): array
    {
        return [
            'target_type'  => 'sometimes|string|max:50',
            'report_date'  => 'sometimes|date',
            'reason'       => 'sometimes|string',
            'road_id'      => 'nullable|integer|exists:roads,id',
            'status'       => 'sometimes|string|in:pending,in_progress,resolved,rejected',
            'photo'        => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
        ];
    }

    public function messages(): array
    {
        return [
            'target_type.max'  => 'Le type de cible ne doit pas dépasser 50 caractères.',
            'report_date.date' => 'La date du signalement doit être une date valide.',
            'road_id.exists'   => 'La route sélectionnée est introuvable.',
            'status.in'        => 'Le statut doit être : pending, in_progress, resolved ou rejected.',
            'photo.image'      => 'Le fichier doit être une image.',
            'photo.mimes'      => 'L\'image doit être au format jpeg, png, jpg ou gif.',
            'photo.max'        => 'L\'image ne doit pas dépasser 5 Mo.',
        ];
    }
}
