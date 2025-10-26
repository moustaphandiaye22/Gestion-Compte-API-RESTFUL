<?php

namespace App\Http\Controllers;

use App\Http\Requests\ListComptesRequest;
use App\Http\Requests\StoreCompteRequest;
use App\Http\Requests\UpdateCompteRequest;
use App\Http\Resources\CompteCollection;
use App\Http\Resources\CompteResource;
use App\Models\Compte;
use App\Services\CompteService;
use App\Traits\ApiResponseTrait;
use App\Exceptions\CompteNotFoundException;
use App\Exceptions\UnauthorizedAccessException;
use Illuminate\Http\Request;

/**
 * @OA\Info(
 *     title="Gestion Compte API",
 *     description="API for managing bank accounts",
 *     version="1.0.0"
 * )
 */

/**
 * @group Comptes
 *
 * APIs pour la gestion des comptes bancaires
 */
class CompteController extends Controller
{
    use ApiResponseTrait;

    protected CompteService $compteService;

    public function __construct(CompteService $compteService)
    {
        $this->compteService = $compteService;
    }

    /**
     * @OA\Get(
     *     path="/api/v1/comptes",
     *     summary="Lister tous les comptes",
     *     description="Liste tous les comptes avec filtrage, tri et pagination. Admin peut voir tous les comptes, Client ne voit que ses comptes. Seuls les comptes non supprimés, de type cheque ou epargne, et actifs sont retournés.",
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Numéro de page (default: 1)",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Nombre d'éléments par page (default: 10, max: 100)",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         description="Filtrer par type (epargne, cheque)",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="statut",
     *         in="query",
     *         description="Filtrer par statut (actif, bloque, ferme)",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Recherche par titulaire ou numéro",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="sort",
     *         in="query",
     *         description="Tri (dateCreation, solde, titulaire)",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="order",
     *         in="query",
     *         description="Ordre (asc, desc)",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="string", example="550e8400-e29b-41d4-a716-446655440000"),
     *                 @OA\Property(property="numeroCompte", type="string", example="C00123456"),
     *                 @OA\Property(property="titulaire", type="string", example="Amadou Diallo"),
     *                 @OA\Property(property="type", type="string", example="epargne"),
     *                 @OA\Property(property="solde", type="number", example=1250000),
     *                 @OA\Property(property="devise", type="string", example="FCFA"),
     *                 @OA\Property(property="dateCreation", type="string", format="date-time", example="2023-03-15T00:00:00Z"),
     *                 @OA\Property(property="statut", type="string", example="bloque"),
     *                 @OA\Property(property="motifBlocage", type="string", example="Inactivité de 30+ jours"),
     *                 @OA\Property(property="metadata", type="object",
     *                     @OA\Property(property="derniereModification", type="string", format="date-time", example="2023-06-10T14:30:00Z"),
     *                     @OA\Property(property="version", type="integer", example=1)
     *                 )
     *             )),
     *             @OA\Property(property="pagination", type="object",
     *                 @OA\Property(property="currentPage", type="integer", example=1),
     *                 @OA\Property(property="totalPages", type="integer", example=3),
     *                 @OA\Property(property="totalItems", type="integer", example=25),
     *                 @OA\Property(property="itemsPerPage", type="integer", example=10),
     *                 @OA\Property(property="hasNext", type="boolean", example=true),
     *                 @OA\Property(property="hasPrevious", type="boolean", example=false)
     *             ),
     *             @OA\Property(property="links", type="object",
     *                 @OA\Property(property="self", type="string", example="/api/v1/comptes?page=1&limit=10"),
     *                 @OA\Property(property="next", type="string", example="/api/v1/comptes?page=2&limit=10"),
     *                 @OA\Property(property="first", type="string", example="/api/v1/comptes?page=1&limit=10"),
     *                 @OA\Property(property="last", type="string", example="/api/v1/comptes?page=3&limit=10")
     *             )
     *         )
     *     )
     * )
     */
    public function index(ListComptesRequest $request)
    {
        // Pour le moment, sans authentification, traiter comme admin
        $isAdmin = true;
        $clientId = null;

        // Récupération des comptes avec le service
        $comptes = $this->compteService->getComptesWithFilters($request, $clientId);

        return new CompteCollection($comptes);
    }

    /**
     * Créer un nouveau compte
     */
    public function store(StoreCompteRequest $request)
    {
        
    }

    /**
       * @OA\Get(
       *     path="/api/v1/comptes/{id}",
       *     summary="Afficher un compte spécifique",
       *     description="Récupère un compte spécifique par ID. Pour le moment, sans authentification, traiter comme admin. Recherche d'abord en local pour les comptes actifs (cheque/epargne), puis en serverless si non trouvé.",
       *     @OA\Parameter(
       *         name="id",
       *         in="path",
       *         description="L'ID du compte",
       *         required=true,
       *         @OA\Schema(type="string")
       *     ),
       *     @OA\Response(
       *         response=200,
       *         description="Successful response",
       *         @OA\JsonContent(
       *             @OA\Property(property="success", type="boolean", example=true),
       *             @OA\Property(property="data", type="object",
       *                 @OA\Property(property="id", type="string", example="550e8400-e29b-41d4-a716-446655440000"),
       *                 @OA\Property(property="numeroCompte", type="string", example="C00123456"),
       *                 @OA\Property(property="titulaire", type="string", example="Amadou Diallo"),
       *                 @OA\Property(property="type", type="string", example="epargne"),
       *                 @OA\Property(property="solde", type="number", example=1250000),
       *                 @OA\Property(property="devise", type="string", example="FCFA"),
       *                 @OA\Property(property="dateCreation", type="string", format="date-time", example="2023-03-15T00:00:00Z"),
       *                 @OA\Property(property="statut", type="string", example="bloque"),
       *                 @OA\Property(property="motifBlocage", type="string", example="Inactivité de 30+ jours"),
       *                 @OA\Property(property="metadata", type="object",
       *                     @OA\Property(property="derniereModification", type="string", format="date-time", example="2023-06-10T14:30:00Z"),
       *                     @OA\Property(property="version", type="integer", example=1)
       *                 )
       *             )
       *         )
       *     ),
       *     @OA\Response(
       *         response=404,
       *         description="Compte not found",
       *         @OA\JsonContent(
       *             @OA\Property(property="success", type="boolean", example=false),
       *             @OA\Property(property="error", type="object",
       *                 @OA\Property(property="code", type="string", example="COMPTE_NOT_FOUND"),
       *                 @OA\Property(property="message", type="string", example="Le compte avec l'ID spécifié n'existe pas"),
       *                 @OA\Property(property="details", type="object",
       *                     @OA\Property(property="compteId", type="string", example="550e8400-e29b-41d4-a716-446655440000")
       *                 )
       *             )
       *         )
       *     )
       * )
       */
    public function show(string $id)
    {
        // Pour le moment, sans authentification, traiter comme admin
        $isAdmin = true;
        $clientId = null;

        // Use service to find account with search strategy
        $compte = $this->compteService->findCompteById($id, $clientId);

        if (!$compte) {
            throw new CompteNotFoundException();
        }

        return $this->successResponse(new CompteResource($compte));
    }

    /**
     * Mettre à jour un compte
     */
    public function update(UpdateCompteRequest $request, string $id)
    {
       
    }

    /**
     * Supprimer un compte (soft delete)
     */
    public function destroy(string $id)
    {
        
    }

    /**
      * @OA\Get(
      *     path="/api/v1/comptes-archives",
      *     summary="Récupérer les comptes archivés",
      *     description="Liste tous les comptes archivés (statut 'Supprime') avec pagination. Accessible uniquement aux administrateurs.",
      *     @OA\Parameter(
      *         name="page",
      *         in="query",
      *         description="Numéro de page (default: 1)",
      *         required=false,
      *         @OA\Schema(type="integer")
      *     ),
      *     @OA\Parameter(
      *         name="limit",
      *         in="query",
      *         description="Nombre d'éléments par page (default: 10, max: 100)",
      *         required=false,
      *         @OA\Schema(type="integer")
      *     ),
      *     @OA\Response(
      *         response=200,
      *         description="Successful response",
      *         @OA\JsonContent(
      *             @OA\Property(property="success", type="boolean", example=true),
      *             @OA\Property(property="data", type="array", @OA\Items(type="object")),
      *             @OA\Property(property="pagination", type="object"),
      *             @OA\Property(property="links", type="object")
      *         )
      *     )
      * )
      */
    public function archives()
    {
        // For archived Epargne accounts, fetch from cloud
        if (request('type') === 'Epargne' || !request('type')) {
            // Simulate fetching from cloud
            $cloudData = $this->fetchFromCloud();
            $comptes = $this->paginateCloudData($cloudData);
        } else {
            $comptes = Compte::withoutGlobalScope('nonSupprime')
                 ->with('client')
                 ->where('statut', 'Supprime')
                 ->where('type', 'Epargne')
                 ->paginate(request('limit', 10));
        }

        return new CompteCollection($comptes);
    }

    private function fetchFromCloud()
    {
        // Placeholder for cloud API call
        // In real implementation, use Http::get('https://cloud-api.example.com/archived-epargne')
        // For demo, return empty or mock data
        return collect([]);
    }

    private function paginateCloudData($data)
    {
        $perPage = request('limit', 10);
        return $data->paginate($perPage);
    }
}
