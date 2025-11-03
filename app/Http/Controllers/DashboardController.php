<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;

/**
 * @group Dashboard
 *
 * APIs pour les statistiques du tableau de bord
 */
class DashboardController extends Controller
{
    use ApiResponseTrait;

    protected DashboardService $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    /**
     * @OA\Get(
     *     path="/ndiaye/v1/dashboard",
     *     summary="Obtenir les statistiques du tableau de bord",
     *     description="Récupère toutes les statistiques nécessaires pour le tableau de bord. Pour les administrateurs: total comptes, balance totale, nombre de transactions, dernières transactions et comptes créés aujourd'hui. Pour les clients: nombre de comptes, balance totale de leurs comptes, nombre de transactions, dernières transactions et liste de leurs comptes.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Statistiques récupérées avec succès",
     *         @OA\JsonContent(
     *             oneOf={
     *                 @OA\Schema(
     *                     title="Admin Dashboard",
     *                     @OA\Property(property="success", type="boolean", example=true),
     *                     @OA\Property(property="data", type="object",
     *                         @OA\Property(property="totalComptes", type="integer", example=150),
     *                         @OA\Property(property="balanceTotale", type="number", example=25000000),
     *                         @OA\Property(property="nombreTransactions", type="integer", example=1250),
     *                         @OA\Property(property="dernieresTransactions", type="array", @OA\Items(
     *                             @OA\Property(property="id", type="string", example="550e8400-e29b-41d4-a716-446655440000"),
     *                             @OA\Property(property="numeroCompte", type="string", example="CPT-2025-ABC123"),
     *                             @OA\Property(property="type", type="string", example="Depot"),
     *                             @OA\Property(property="montant", type="number", example=50000),
     *                             @OA\Property(property="dateTransaction", type="string", format="date-time", example="2025-11-02T10:30:00Z"),
     *                             @OA\Property(property="description", type="string", example="Dépôt via mobile")
     *                         )),
     *                         @OA\Property(property="comptesCreesAujourdhui", type="array", @OA\Items(
     *                             @OA\Property(property="id", type="string", example="550e8400-e29b-41d4-a716-446655440001"),
     *                             @OA\Property(property="numeroCompte", type="string", example="CPT-2025-DEF456"),
     *                             @OA\Property(property="titulaire", type="string", example="Moustapha Ndiaye"),
     *                             @OA\Property(property="type", type="string", example="Cheque"),
     *                             @OA\Property(property="dateCreation", type="string", format="date-time", example="2025-11-02T08:15:00Z")
     *                         ))
     *                     )
     *                 ),
     *                 @OA\Schema(
     *                     title="Client Dashboard",
     *                     @OA\Property(property="success", type="boolean", example=true),
     *                     @OA\Property(property="data", type="object",
     *                         @OA\Property(property="nombreComptes", type="integer", example=3),
     *                         @OA\Property(property="balanceTotale", type="number", example=1500000),
     *                         @OA\Property(property="nombreTransactions", type="integer", example=45),
     *                         @OA\Property(property="dernieresTransactions", type="array", @OA\Items(
     *                             @OA\Property(property="id", type="string", example="550e8400-e29b-41d4-a716-446655440000"),
     *                             @OA\Property(property="numeroCompte", type="string", example="CPT-2025-ABC123"),
     *                             @OA\Property(property="type", type="string", example="Depot"),
     *                             @OA\Property(property="montant", type="number", example=50000),
     *                             @OA\Property(property="dateTransaction", type="string", format="date-time", example="2025-11-02T10:30:00Z"),
     *                             @OA\Property(property="description", type="string", example="Dépôt via mobile")
     *                         )),
     *                         @OA\Property(property="comptes", type="array", @OA\Items(
     *                             @OA\Property(property="id", type="string", example="550e8400-e29b-41d4-a716-446655440001"),
     *                             @OA\Property(property="numeroCompte", type="string", example="CPT-2025-DEF456"),
     *                             @OA\Property(property="titulaire", type="string", example="Moustapha Ndiaye"),
     *                             @OA\Property(property="type", type="string", example="Cheque"),
     *                             @OA\Property(property="solde", type="number", example=500000),
     *                             @OA\Property(property="dateCreation", type="string", format="date-time", example="2025-11-02T08:15:00Z")
     *                         ))
     *                     )
     *                 )
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Authentification requise",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="error", type="object",
     *                 @OA\Property(property="code", type="string", example="UNAUTHENTICATED"),
     *                 @OA\Property(property="message", type="string", example="Authentification requise")
     *             )
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $isAdmin = $user->userable_type === 'App\\Models\\Admin';

        if ($isAdmin) {
            $data = $this->dashboardService->getAdminDashboardStats();
            return $this->successResponse($data, 'Statistiques du tableau de bord récupérées avec succès');
        } else {
            $clientId = $user->userable_id;
            $data = $this->dashboardService->getClientDashboardStats($clientId);
            return $this->successResponse($data, 'Tableau de bord client récupéré avec succès');
        }
    }
}