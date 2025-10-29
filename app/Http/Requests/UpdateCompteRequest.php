<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCompteRequest extends FormRequest
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
        $compteId = $this->route('compte') ? $this->route('compte') : null;

        return [
            // Au moins un champ doit être fourni
            'titulaire' => 'nullable|string|max:255',
            'informationsClient' => 'nullable|array',
            'informationsClient.telephone' => 'nullable|string|regex:/^\+221[76-8][0-9]{7}$/|unique:clients,telephone',
            'informationsClient.email' => 'nullable|email|unique:clients,email',
            'informationsClient.password' => 'nullable|string|min:8',
            'informationsClient.nci' => 'nullable|string|regex:/^[0-9]{13}[A-Z]$/|unique:clients,cni',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $hasTitulaire = $this->filled('titulaire');
            $hasClientInfo = $this->filled('informationsClient') &&
                           collect($this->input('informationsClient', []))->filter()->isNotEmpty();

            if (!$hasTitulaire && !$hasClientInfo) {
                $validator->errors()->add('general', 'Au moins un champ de modification doit être fourni.');
            }
        });
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'titulaire.string' => 'Le titulaire doit être une chaîne de caractères.',
            'titulaire.max' => 'Le titulaire ne peut pas dépasser 255 caractères.',
            'informationsClient.telephone.regex' => 'Le numéro de téléphone doit être un numéro sénégalais valide (format: +221XXXXXXXXX).',
            'informationsClient.telephone.unique' => 'Ce numéro de téléphone est déjà utilisé.',
            'informationsClient.email.email' => 'L\'adresse email doit être valide.',
            'informationsClient.email.unique' => 'Cette adresse email est déjà utilisée.',
            'informationsClient.password.min' => 'Le mot de passe doit contenir au moins 8 caractères.',
            'informationsClient.nci.regex' => 'Le numéro CNI doit être au format 13 chiffres suivis d\'une lettre majuscule.',
            'informationsClient.nci.unique' => 'Ce numéro CNI est déjà utilisé.',
            'general' => 'Au moins un champ de modification doit être fourni.',
        ];
    }
}
