<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SenegalesePhoneAndNci implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($attribute === 'client.telephone') {
            // Senegalese phone: +221, 00221, or local format with valid operator codes
            // Operators: 30, 33, 70, 72, 75, 76, 77, 78
            if (!preg_match('/^(?:\+221|00221)?(?:30|33|70|72|75|76|77|78)\d{7}$/', $value)) {
                $fail('Le numéro de téléphone doit être un numéro valide au Sénégal (ex : +221771234567, 00221771234567, ou 771234567). Préfixes autorisés : 30, 33, 70, 72, 75, 76, 77, 78.');
            }
        } elseif ($attribute === 'client.nci') {
            // Senegalese CNI: exactly 13 digits
            if (!preg_match('/^\d{13}$/', $value)) {
                $fail('Le numéro CNI doit être composé de 13 chiffres.');
            }
        }
    }
}
