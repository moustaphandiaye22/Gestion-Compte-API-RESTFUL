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
            'motif' => 'required|string|max:255',
            'duree' => 'required|integer|min:1',
            'unite' => 'required|string|in:heures,jours,semaines,mois',
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
            'motif.required' => 'Le motif de blocage est obligatoire.',
            'motif.string' => 'Le motif doit être une chaîne de caractères.',
            'motif.max' => 'Le motif ne doit pas dépasser 255 caractères.',
            'duree.required' => 'La durée de blocage est obligatoire.',
            'duree.integer' => 'La durée doit être un nombre entier.',
            'duree.min' => 'La durée doit être d\'au moins 1.',
            'unite.required' => 'L\'unité de durée est obligatoire.',
            'unite.string' => 'L\'unité doit être une chaîne de caractères.',
            'unite.in' => 'L\'unité doit être l\'une des valeurs suivantes : heures, jours, semaines, mois.',
        ];
    }
}
