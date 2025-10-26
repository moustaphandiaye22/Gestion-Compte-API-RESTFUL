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
            // Senegalese phone: +221 followed by operator codes and 7 digits, or 221 followed by 6 digits
            // Operators: Orange (77, 78), Free (70, 71, 72, 73, 74, 75, 76), Expresso (79)
            if (str_starts_with($value, '+221')) {
                if (!preg_match('/^\+221(7[0-9]|77|78)[0-9]{7}$/', $value)) {
                    $fail('Le numéro de téléphone doit être un numéro de téléphone portable sénégalais valide avec un opérateur reconnu (Orange: 77-78, Free: 70-76, Expresso: 79). Ex: +221771234567.');
                }
            } elseif (str_starts_with($value, '221')) {
                if (!preg_match('/^221(7[0-9]|77|78)[0-9]{6}$/', $value)) {
                    $fail('Le numéro de téléphone doit être un numéro de téléphone portable sénégalais valide avec un opérateur reconnu (Orange: 77-78, Free: 70-76, Expresso: 79). Ex: 221771234567.');
                }
            } else {
                $fail('Le numéro de téléphone doit être un numéro de téléphone portable sénégalais valide avec un opérateur reconnu (Orange: 77-78, Free: 70-76, Expresso: 79). Ex: +221771234567.');
            }
        } elseif ($attribute === 'client.nci') {
            // Senegalese CNI: 13 digits starting with birth year (19 or 20 for modern)
            if (!preg_match('/^(19|20)[0-9]{11}$/', $value)) {
                $fail('Le numéro CNI doit être composé de 13 chiffres commençant par l\'année de naissance (19 ou 20).');
            }
        }
    }
}
