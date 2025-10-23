<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListComptesRequest extends FormRequest
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
            'page' => 'integer|min:1',
            'limit' => 'integer|min:1|max:100',
            'type' => 'nullable|in:Epargne,Cheque',
            'statut' => 'nullable|in:Actif,Bloque,Ferme',
            'devise' => 'nullable|string|max:10',
            'search' => 'nullable|string|max:255',
            'sort' => 'nullable|in:dateCreation,solde,titulaire,numeroCompte,type,statut,devise',
            'order' => 'nullable|in:asc,desc',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
            'solde_min' => 'nullable|numeric|min:0',
            'solde_max' => 'nullable|numeric|min:0',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'page.integer' => 'Le numéro de page doit être un entier.',
            'page.min' => 'Le numéro de page doit être au moins 1.',
            'limit.integer' => 'La limite doit être un entier.',
            'limit.min' => 'La limite doit être au moins 1.',
            'limit.max' => 'La limite ne peut pas dépasser 100.',
            'type.in' => 'Le type doit être soit "Epargne" soit "Cheque".',
            'statut.in' => 'Le statut doit être "Actif", "Bloque" ou "Ferme".',
            'devise.string' => 'La devise doit être une chaîne de caractères.',
            'devise.max' => 'La devise ne peut pas dépasser 10 caractères.',
            'search.string' => 'La recherche doit être une chaîne de caractères.',
            'search.max' => 'La recherche ne peut pas dépasser 255 caractères.',
            'sort.in' => 'Le tri doit être un champ valide.',
            'order.in' => 'L\'ordre doit être "asc" ou "desc".',
            'date_from.date' => 'La date de début doit être une date valide.',
            'date_to.date' => 'La date de fin doit être une date valide.',
            'solde_min.numeric' => 'Le solde minimum doit être un nombre.',
            'solde_min.min' => 'Le solde minimum doit être positif.',
            'solde_max.numeric' => 'Le solde maximum doit être un nombre.',
            'solde_max.min' => 'Le solde maximum doit être positif.',
        ];
    }
}
