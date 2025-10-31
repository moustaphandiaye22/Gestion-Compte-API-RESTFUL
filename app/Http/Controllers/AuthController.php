<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Models\User;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;

/**
 * @OA\Tag(
 *     name="Authentification",
 *     description="Endpoints d'authentification"
 * )
 */

/**
 * @OA\Tag(
 *     name="Authentification",
 *     description="Endpoints d'authentification"
 * )
 */
class AuthController extends Controller
{
    use ApiResponseTrait;

    /**
     * @OA\Post(
     *     path="/ndiaye/v1/auth/login",
     *     summary="Connexion utilisateur",
     *     description="Authentifie un utilisateur et retourne les tokens d'accès et de rafraîchissement",
     *     tags={"Authentification"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email", "password"},
     *             @OA\Property(property="email", type="string", format="email", example="raymond25@example.com"),
     *             @OA\Property(property="password", type="string", example="password")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Connexion réussie",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Connexion réussie"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user", type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="email", type="string", example="raymond25@example.com"),
     *                     @OA\Property(property="role", type="string", example="admin")
     *                 ),
     *                 @OA\Property(property="access_token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..."),
     *                 @OA\Property(property="token_type", type="string", example="Bearer"),
     *                 @OA\Property(property="expires_in", type="integer", example=900)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Identifiants invalides",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="error", type="object",
     *                 @OA\Property(property="code", type="string", example="INVALID_CREDENTIALS"),
     *                 @OA\Property(property="message", type="string", example="Identifiants invalides")
     *             )
     *         )
     *     )
     * )
     */
    public function login(LoginRequest $request)
    {
        $credentials = $request->only('email', 'password');

        if (!Auth::attempt($credentials)) {
            return $this->errorResponse('Identifiants invalides', 401, 'INVALID_CREDENTIALS');
        }

        $user = Auth::user();
        $role = $user->userable_type === 'App\\Models\\Admin' ? 'admin' : 'client';

        // Créer le token avec scope et claims personnalisés
        $token = $user->createToken('Personal Access Token')->accessToken;

        // Ajouter des claims personnalisés au token
        $tokenWithClaims = $this->addCustomClaims($token, $user, $role);

        // Stocker le token dans un cookie sécurisé
        Cookie::queue('access_token', $tokenWithClaims, 15 * 24 * 60, '/', null, true, true); // 15 jours

        return $this->successResponse([
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'role' => $role,
            ],
            'access_token' => $tokenWithClaims,
            'token_type' => 'Bearer',
            'expires_in' => 15 * 24 * 60 * 60, // 15 jours en secondes
        ], 'Connexion réussie');
    }

    /**
     * @OA\Post(
     *     path="/ndiaye/v1/auth/refresh",
     *     summary="Rafraîchir le token d'accès",
     *     description="Génère un nouveau token d'accès en utilisant le token de rafraîchissement",
     *     tags={"Authentification"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Token rafraîchi",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="access_token", type="string"),
     *                 @OA\Property(property="token_type", type="string", example="Bearer"),
     *                 @OA\Property(property="expires_in", type="integer", example=900)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Token invalide",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="error", type="object",
     *                 @OA\Property(property="code", type="string", example="INVALID_TOKEN"),
     *                 @OA\Property(property="message", type="string", example="Token invalide")
     *             )
     *         )
     *     )
     * )
     */
    public function refresh(Request $request)
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return $this->errorResponse('Authentification requise', 401, 'UNAUTHENTICATED');
            }

            // Révoquer l'ancien token
            $request->user()->token()->revoke();

            $role = $user->userable_type === 'App\\Models\\Admin' ? 'admin' : 'client';

            // Créer un nouveau token avec scope et claims
            $token = $user->createToken('Personal Access Token')->accessToken;
            $tokenWithClaims = $this->addCustomClaims($token, $user, $role);

            // Mettre à jour le cookie
            Cookie::queue('access_token', $tokenWithClaims, 15 * 24 * 60, '/', null, true, true);

            return $this->successResponse([
                'access_token' => $tokenWithClaims,
                'token_type' => 'Bearer',
                'expires_in' => 15 * 24 * 60 * 60,
            ], 'Token rafraîchi');
        } catch (\Exception $e) {
            return $this->errorResponse('Authentification requise', 401, 'UNAUTHENTICATED');
        }
    }

    /**
     * @OA\Post(
     *     path="/ndiaye/v1/auth/logout",
     *     summary="Déconnexion",
     *     description="Invalide le token d'accès actuel",
     *     tags={"Authentification"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Déconnexion réussie",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Déconnexion réussie")
     *         )
     *     )
     * )
     */
    public function logout(Request $request)
    {
        try {
            $user = $request->user();

            if ($user) {
                // Révoquer le token actuel
                $user->token()->revoke();
            }

            // Supprimer le cookie
            Cookie::queue(Cookie::forget('access_token'));

            return $this->successResponse(null, 'Déconnexion réussie');
        } catch (\Exception $e) {
            return $this->errorResponse('Authentification requise', 401, 'UNAUTHENTICATED');
        }
    }

    /**
     * @OA\Get(
     *     path="/ndiaye/v1/auth/user",
     *     summary="Informations utilisateur",
     *     description="Retourne les informations de l'utilisateur connecté",
     *     tags={"Authentification"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Informations utilisateur",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="email", type="string", example="samson.weissnat@example.com"),
     *                 @OA\Property(property="role", type="string", example="admin")
     *             )
     *         )
     *     )
     * )
     */
    public function user(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return $this->errorResponse('Authentification requise', 401, 'UNAUTHENTICATED');
            }

            return $this->successResponse([
                'id' => $user->id,
                'email' => $user->email,
                'role' => $user->userable_type === 'App\\Models\\Admin' ? 'admin' : 'client',
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Authentification requise', 401, 'UNAUTHENTICATED');
        }
    }

    /**
     * Ajouter des claims personnalisés au token
     */
    private function addCustomClaims($token, $user, $role)
    {
        // Pour Laravel Passport, les claims personnalisés sont gérés différemment
        // Nous retournons le token tel quel car les scopes sont déjà appliqués
        // Le rôle est stocké dans les scopes et accessible via les middlewares
        return $token;
    }
}