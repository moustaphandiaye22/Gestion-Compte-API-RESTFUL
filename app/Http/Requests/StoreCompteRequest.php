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
            'type' => 'required|in:Cheque,Epargne',
            'soldeInitial' => 'required|numeric|min:10000',
            'devise' => 'nullable|string|max:10',
            'client.id' => 'nullable|uuid',
            'client.titulaire' => 'required|string|max:255',
            'client.nci' => ['required', 'string', new \App\Rules\SenegalesePhoneAndNci()],
            'client.email' => 'required|email',
            'client.telephone' => ['required', 'string', new \App\Rules\SenegalesePhoneAndNci()],
            'client.adresse' => 'required|string|max:255',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'type.required' => 'Le type de compte est obligatoire.',
            'type.in' => 'Le type doit être Cheque ou Epargne.',
            'soldeInitial.required' => 'Le solde initial est obligatoire.',
            'soldeInitial.numeric' => 'Le solde initial doit être un nombre.',
            'soldeInitial.min' => 'Le solde initial doit être supérieur ou égal à 10000.',
            'client.titulaire.required' => 'Le titulaire est obligatoire.',
            'client.nci.required' => 'Le numéro CNI est obligatoire.',
            'client.email.required' => 'L\'email est obligatoire.',
            'client.email.email' => 'L\'email doit être valide.',
            'client.email.unique' => 'Cet email est déjà utilisé.',
            'client.telephone.required' => 'Le téléphone est obligatoire.',
            'client.telephone.unique' => 'Ce numéro de téléphone est déjà utilisé.',
            'client.adresse.required' => 'L\'adresse est obligatoire.',
            'client.telephone.*' => 'Le numéro de téléphone doit être un numéro de téléphone portable sénégalais valide avec un opérateur reconnu (Orange: 77-78, Free: 70-76, Expresso: 79). Ex: +221771234567.',
            'client.nci.*' => 'Le numéro CNI doit être composé de 13 chiffres commençant par l\'année de naissance (19 ou 20).',
        ];
    }
}
