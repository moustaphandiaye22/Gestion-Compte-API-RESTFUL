<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCompteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Adjust authorization logic as needed
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'numeroCompte' => 'nullable|string|unique:comptes,numeroCompte',
            'titulaire' => 'required|string|max:255',
            'type' => 'required|in:Epargne,Cheque',
            'devise' => 'nullable|string|max:10',
            'dateCreation' => 'nullable|date',
            'statut' => 'nullable|in:Actif,Bloque,Ferme',
            'metadata' => 'nullable|array',
            'client_id' => 'required|uuid|exists:clients,id',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'numeroCompte.unique' => 'Ce numéro de compte existe déjà.',
            'titulaire.required' => 'Le titulaire est obligatoire.',
            'type.required' => 'Le type de compte est obligatoire.',
            'type.in' => 'Le type doit être Epargne ou Cheque.',
            'client_id.required' => 'Le client est obligatoire.',
            'client_id.exists' => 'Le client sélectionné n\'existe pas.',
        ];
    }
}
