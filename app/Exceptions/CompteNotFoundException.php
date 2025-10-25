<?php

namespace App\Exceptions;

use Exception;

class CompteNotFoundException extends Exception
{
    public function __construct(string $compteId = null)
    {
        $message = 'Le compte avec l\'ID spécifié n\'existe pas';
        parent::__construct($message, 404);
    }

    public function render()
    {
        return response()->json([
            'success' => false,
            'error' => [
                'code' => 'COMPTE_NOT_FOUND',
                'message' => $this->getMessage(),
                'details' => [
                    'compteId' => request()->route('compte') ?? null
                ]
            ]
        ], 404);
    }
}
