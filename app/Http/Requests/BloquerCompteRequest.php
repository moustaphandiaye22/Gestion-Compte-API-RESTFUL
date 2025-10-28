<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BloquerCompteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Pour le moment, sans authentification
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'date_debut_blocage' => 'required|date|after:now',
            'date_fin_blocage' => 'required|date|after:date_debut_blocage',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'date_debut_blocage.required' => 'La date de début de blocage est obligatoire.',
            'date_debut_blocage.date' => 'La date de début de blocage doit être une date valide.',
            'date_debut_blocage.after' => 'La date de début de blocage doit être postérieure à maintenant.',
            'date_fin_blocage.required' => 'La date de fin de blocage est obligatoire.',
            'date_fin_blocage.date' => 'La date de fin de blocage doit être une date valide.',
            'date_fin_blocage.after' => 'La date de fin de blocage doit être postérieure à la date de début.',
        ];
    }
}
