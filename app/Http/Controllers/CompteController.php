<?php

namespace App\Http\Controllers;

use App\Http\Requests\BloquerCompteRequest;
use App\Http\Requests\DebloquerCompteRequest;
use App\Http\Requests\ListComptesRequest;
use App\Http\Requests\StoreCompteRequest;
use App\Http\Requests\UpdateCompteRequest;
use App\Http\Resources\CompteCollection;
use App\Http\Resources\CompteResource;
use App\Models\Compte;
use App\Models\Client;
use App\Models\User;
use App\Models\Transaction;
use App\Services\CompteService;
use App\Traits\ApiResponseTrait;
use App\Exceptions\CompteNotFoundException;
use App\Exceptions\UnauthorizedAccessException;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Pagination\LengthAwarePaginator;

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
     *     path="/ndiaye/v1/comptes",
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
     *         description="Filtrer par statut (actif uniquement)",
     *         required=false,
     *         @OA\Schema(type="string", enum={"actif"})
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
     *                 @OA\Property(property="self", type="string", example="/ndiaye/v1/comptes?page=1&limit=10"),
     *                 @OA\Property(property="next", type="string", example="/ndiaye/v1/comptes?page=2&limit=10"),
     *                 @OA\Property(property="first", type="string", example="/ndiaye/v1/comptes?page=1&limit=10"),
     *                 @OA\Property(property="last", type="string", example="/ndiaye/v1/comptes?page=3&limit=10")
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

        // Load client relationship for the response
        $comptes->load('client');

        return new CompteCollection($comptes);
    }

    /**
      * @OA\Post(
      *     path="/ndiaye/v1/comptes",
      *     summary="Créer un nouveau compte",
      *     description="Crée un nouveau compte bancaire. Vérifie l'existence du client, le crée si nécessaire, génère un mot de passe et un code, crée le compte, effectue un dépôt initial, et envoie des notifications par email et SMS.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"type", "soldeInitial", "client"},
     *             @OA\Property(property="type", type="string", enum={"Cheque", "Epargne"}, example="Cheque"),
     *             @OA\Property(property="soldeInitial", type="number", minimum=10000, example=500000),
     *             @OA\Property(property="devise", type="string", example="FCFA"),
     *             @OA\Property(property="client", type="object",
     *                 required={"titulaire", "nci", "email", "telephone", "adresse"},
     *                 @OA\Property(property="id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
     *                 @OA\Property(property="titulaire", type="string", example="Hawa BB Wane"),
     *             @OA\Property(property="nci", type="string", example="1987654321098"),
     *                 @OA\Property(property="email", type="string", format="email", example="cheikh.sy@example.com"),
     *                 @OA\Property(property="telephone", type="string", example="+221771234567"),
     *                 @OA\Property(property="adresse", type="string", example="Dakar, Sénégal")
     *             )
     *         )
     *     ),
      *     @OA\Response(
      *         response=201,
      *         description="Compte créé avec succès",
      *         @OA\JsonContent(
      *             @OA\Property(property="success", type="boolean", example=true),
      *             @OA\Property(property="message", type="string", example="Compte créé avec succès"),
      *             @OA\Property(property="data", type="object",
      *                 @OA\Property(property="id", type="string", example="660f9511-f30c-52e5-b827-557766551111"),
      *                 @OA\Property(property="numeroCompte", type="string", example="C00123460"),
      *                 @OA\Property(property="titulaire", type="string", example="Cheikh Sy"),
      *                 @OA\Property(property="type", type="string", example="Cheque"),
      *                 @OA\Property(property="solde", type="number", example=500000),
      *                 @OA\Property(property="devise", type="string", example="FCFA"),
      *                 @OA\Property(property="dateCreation", type="string", format="date-time", example="2025-10-19T10:30:00Z"),
      *                 @OA\Property(property="statut", type="string", example="Actif"),
      *                 @OA\Property(property="metadata", type="object",
      *                     @OA\Property(property="derniereModification", type="string", format="date-time", example="2025-10-19T10:30:00Z"),
      *                     @OA\Property(property="version", type="integer", example=1)
      *                 )
      *             )
      *         )
      *     ),
      *     @OA\Response(
      *         response=400,
      *         description="Erreurs de validation",
      *         @OA\JsonContent(
      *             @OA\Property(property="success", type="boolean", example=false),
      *             @OA\Property(property="error", type="object",
      *                 @OA\Property(property="code", type="string", example="VALIDATION_ERROR"),
      *                 @OA\Property(property="message", type="string", example="Les données fournies sont invalides"),
      *                 @OA\Property(property="details", type="object",
      *                     @OA\Property(property="soldeInitial", type="string", example="Le solde initial doit être supérieur à 0")
      *                 )
      *             )
      *         )
      *     )
      * )
      */
    public function store(StoreCompteRequest $request)
    {
        $data = $request->validated();

        // Find or create client
         [$client, $password] = $this->findOrCreateClient($data['client']);

         // Create account
         $compte = $this->createCompte($data, $client);

         // Create initial deposit transaction
         $this->createInitialDeposit($compte, $data['soldeInitial']);

         // Dispatch event for notifications
         if ($password) {
             event(new \App\Events\ClientCreated($client, $password));
         }

        // Load client relationship for the response
        $compte->load('client');

        return $this->successResponse(new CompteResource($compte), 'Compte créé avec succès', 201);
    }

    private function findOrCreateClient(array $clientData): array
    {
         // If client.id is provided, try to find by id
         if (isset($clientData['id']) && !empty($clientData['id'])) {
             $client = Client::find($clientData['id']);
             if ($client) {
                 return [$client, null];
             }
         }

         // Check if client exists by email or telephone
         $client = Client::where('email', $clientData['email'])
                         ->orWhere('telephone', $clientData['telephone'])
                         ->first();

         if (!$client) {
             // Create new client
             [$client, $password] = $this->createClient($clientData);
             return [$client, $password];
         }

         return [$client, null];
    }

    private function createClient(array $clientData): array
    {
         // Split titulaire into prenom and nom
         $nameParts = explode(' ', $clientData['titulaire'], 2);
         $prenom = $nameParts[0];
         $nom = $nameParts[1] ?? '';

         // Generate password and code
         $password = Str::random(8);
         $code = Str::random(6);

         // Create client first
         $client = Client::create([
             'prenom' => $prenom,
             'nom' => $nom,
             'cni' => $clientData['nci'],
             'telephone' => $clientData['telephone'],
             'email' => $clientData['email'],
             'adresse' => $clientData['adresse'],
         ]);

         // Create user with userable_id and userable_type
         $user = User::create([
             'email' => $clientData['email'],
             'password' => bcrypt($password),
             'code' => $code,
             'userable_id' => $client->id,
             'userable_type' => Client::class,
         ]);

         return [$client, $password];
    }

    private function createCompte(array $data, Client $client)
    {
        return Compte::create([
            'titulaire' => $client->titulaire,
            'type' => ucfirst($data['type']),
            'devise' => $data['devise'] ?? 'FCFA',
            'dateCreation' => now(),
            'statut' => 'Actif',
            'client_id' => $client->id,
        ]);
    }

    private function createInitialDeposit(Compte $compte, $montant)
    {
        Transaction::create([
            'numeroCompte' => $compte->numeroCompte,
            'type' => 'Depot',
            'montant' => $montant,
            'dateTransaction' => now(),
            'description' => 'Dépôt initial',
            'statut' => 'Validee',
            'compte_id' => $compte->id,
        ]);
    }

    /**
       * @OA\Get(
       *     path="/ndiaye/v1/comptes/{id}",
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

        // Load client relationship for the response
        $compte->load('client');

        return $this->successResponse(new CompteResource($compte));
    }


    public function update(UpdateCompteRequest $request, string $id)
    {
        // Pour le moment, sans authentification, traiter comme admin
        $isAdmin = true;

        // Find the account
        $compte = Compte::find($id);
        if (!$compte) {
            throw new CompteNotFoundException();
        }

        // Authorize
        if (!$isAdmin) {
            $this->authorize('update', $compte);
        }

        // Get validated data
        $validated = $request->validated();

        // Update account
        $compte->update($validated);

        // Load client relationship for the response
        $compte->load('client');

        return $this->successResponse(new CompteResource($compte), 'Compte mis à jour avec succès');
    }

    /**
     * @OA\Delete(
     *     path="/ndiaye/v1/comptes/{id}",
     *     summary="Supprimer un compte",
     *     description="Supprime un compte de manière soft (met à jour le statut à 'Ferme' et définit la date de fermeture). Accessible uniquement aux administrateurs.",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="L'ID du compte",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Compte supprimé avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Compte supprimé avec succès"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="string", example="550e8400-e29b-41d4-a716-446655440000"),
     *                 @OA\Property(property="numeroCompte", type="string", example="C00123456"),
     *                 @OA\Property(property="statut", type="string", example="ferme"),
     *                 @OA\Property(property="dateFermeture", type="string", format="date-time", example="2025-10-19T11:15:00Z")
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
     *                 @OA\Property(property="message", type="string", example="Le compte avec l'ID spécifié n'existe pas")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="error", type="object",
     *                 @OA\Property(property="code", type="string", example="UNAUTHORIZED"),
     *                 @OA\Property(property="message", type="string", example="Vous n'avez pas les permissions nécessaires")
     *             )
     *         )
     *     )
     * )
     */
    public function destroy(string $id)
    {
        // Pour le moment, sans authentification, traiter comme admin
        $isAdmin = true;

        // Find the account
        $compte = Compte::find($id);
        if (!$compte) {
            throw new CompteNotFoundException();
        }

        // Check if account is active (only active accounts can be deleted)
        if ($compte->statut !== 'Actif') {
            return $this->errorResponse('Seuls les comptes actifs peuvent être supprimés.', 400, 'VALIDATION_ERROR');
        }

        // Authorize the user
        if ($isAdmin) {
            // Proceed as admin
        } else {
            $this->authorize('delete', $compte);
        }

        // Perform soft delete
        $compte->update([
            'statut' => 'Ferme',
            'dateFermeture' => now(),
        ]);

        // Load client relationship for the response
        $compte->load('client');

        return $this->successResponse(new CompteResource($compte), 'Compte supprimé avec succès');
    }

    /**
      * @OA\Get(
      *     path="/ndiaye/v1/comptes-archives",
      *     summary="[CONSULTATION] Récupérer les comptes archivés",
      *     description="Liste tous les comptes archivés (statut 'Supprime') avec pagination. Accessible uniquement aux administrateurs. Cette endpoint permet de consulter les comptes qui ont été archivés automatiquement ou manuellement.",
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
                 ->where('type', 'Cheque')
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
        $currentPage = request('page', 1);
        $total = $data->count();
        $offset = ($currentPage - 1) * $perPage;
        $items = $data->slice($offset, $perPage);

        return new LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $currentPage,
            ['path' => request()->url(), 'pageName' => 'page']
        );
    }

    /**
     * @OA\Post(
     *     path="/ndiaye/v1/comptes/{compteId}/bloquer",
     *     summary="[ADMIN] Bloquer un compte Epargne - Géré par les Jobs",
     *     description="Cette endpoint est réservée aux administrateurs pour bloquer manuellement un compte Epargne. L'archivage automatique est géré par les Jobs programmés.",
     *     @OA\Parameter(
     *         name="compteId",
     *         in="path",
     *         description="L'ID du compte",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"motif", "duree", "unite"},
     *             @OA\Property(property="motif", type="string", example="Activité suspecte détectée"),
     *             @OA\Property(property="duree", type="integer", example=30),
     *             @OA\Property(property="unite", type="string", enum={"heures", "jours", "semaines", "mois"}, example="jours")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Compte bloqué avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Compte bloqué avec succès"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="string", example="550e8400-e29b-41d4-a716-446655440000"),
     *                 @OA\Property(property="statut", type="string", example="bloque"),
     *                 @OA\Property(property="motifBlocage", type="string", example="Activité suspecte détectée"),
     *                 @OA\Property(property="dateBlocage", type="string", format="date-time", example="2025-10-19T11:20:00Z"),
     *                 @OA\Property(property="dateDeblocagePrevue", type="string", format="date-time", example="2025-11-18T11:20:00Z")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Erreur de validation ou compte non éligible",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="error", type="object",
     *                 @OA\Property(property="code", type="string", example="VALIDATION_ERROR"),
     *                 @OA\Property(property="message", type="string", example="Le compte n'est pas éligible au blocage. Seuls les comptes Epargne actifs peuvent être bloqués.")
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
     *                 @OA\Property(property="message", type="string", example="Le compte avec l'ID spécifié n'existe pas")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="error", type="object",
     *                 @OA\Property(property="code", type="string", example="UNAUTHORIZED"),
     *                 @OA\Property(property="message", type="string", example="Vous n'avez pas les permissions nécessaires")
     *             )
     *         )
     *     )
     * )
     */
    public function bloquer(BloquerCompteRequest $request, string $compteId)
    {
        // Pour le moment, sans authentification, traiter comme admin
        $isAdmin = true;

        // Find the account
        $compte = Compte::find($compteId);
        if (!$compte) {
            throw new CompteNotFoundException();
        }

        // Validate account eligibility: must be Epargne and Actif
        if ($compte->type !== 'Epargne' || $compte->statut !== 'Actif') {
            return $this->errorResponse('Le compte n\'est pas éligible au blocage. Seuls les comptes Epargne actifs peuvent être bloqués.', 400, 'VALIDATION_ERROR');
        }

        // Check if account is already blocked
        if ($compte->isBlocked()) {
            return $this->errorResponse('Le compte est déjà bloqué.', 400, 'VALIDATION_ERROR');
        }

        // Authorize
        if (!$isAdmin) {
            $this->authorize('update', $compte);
        }

        // Get validated data
        $validated = $request->validated();

        // Calculate blocking dates
        $dateBlocage = now();
        $dateDeblocagePrevue = $this->calculateDeblocageDate($dateBlocage, $validated['duree'], $validated['unite']);

        // Update account
        $compte->update([
            'statut' => 'Bloque',
            'motifBlocage' => $validated['motif'],
            'date_debut_blocage' => $dateBlocage,
            'date_fin_blocage' => $dateDeblocagePrevue,
        ]);

        // Load client relationship for the response
        $compte->load('client');

        return $this->successResponse(new CompteResource($compte), 'Compte bloqué avec succès', 200);
    }

    private function calculateDeblocageDate($startDate, $duree, $unite)
    {
        $date = clone $startDate;

        switch ($unite) {
            case 'heures':
                return $date->addHours($duree);
            case 'jours':
                return $date->addDays($duree);
            case 'semaines':
                return $date->addWeeks($duree);
            case 'mois':
                return $date->addMonths($duree);
            default:
                throw new \InvalidArgumentException('Unité de durée invalide');
        }
    }

    public function debloquer(DebloquerCompteRequest $request, string $compteId)
    {
        // Pour le moment, sans authentification, traiter comme admin
        $isAdmin = true;

        // Find the account
        $compte = Compte::find($compteId);
        if (!$compte) {
            throw new CompteNotFoundException();
        }

        // Validate account eligibility: must be Epargne and bloque
        if ($compte->type !== 'Epargne' || $compte->statut !== 'Bloque') {
            return $this->errorResponse('Le compte n\'est pas éligible au déblocage. Seuls les comptes Epargne bloqués peuvent être débloqués.', 400, 'VALIDATION_ERROR');
        }

        // Check if account is actually blocked (considering dates)
        if (!$compte->isBlocked()) {
            return $this->errorResponse('Le compte n\'est pas actuellement bloqué.', 400, 'VALIDATION_ERROR');
        }

        // Authorize
        if (!$isAdmin) {
            $this->authorize('update', $compte);
        }

        // Get validated data
        $validated = $request->validated();

        // Update account
        $compte->update([
            'statut' => 'Actif',
            'motifBlocage' => null, // Clear blocking motif
            'date_debut_blocage' => null,
            'date_fin_blocage' => null,
        ]);

        // Load client relationship for the response
        $compte->load('client');

        return $this->successResponse(new CompteResource($compte), 'Compte débloqué avec succès', 200);
    }

    public function archiver(string $compte)
    {
        // Pour le moment, sans authentification, traiter comme admin
        $isAdmin = true;

        // Find the account
        $compte = Compte::find($compte);
        if (!$compte) {
            throw new CompteNotFoundException();
        }

        // Authorize
        if (!$isAdmin) {
            $this->authorize('delete', $compte);
        }

        // Archive the account
        $compte->update(['statut' => 'Supprime']);

        // Archive all transactions for this account
        Transaction::where('compte_id', $compte->id)
            ->update(['statut' => 'Archivee']);

        // Load client relationship for the response
        $compte->load('client');

        return $this->successResponse(new CompteResource($compte), 'Compte archivé avec succès');
    }

    /**
     * @OA\Get(
     *     path="/ndiaye/v1/comptes/recherche/{numero}",
     *     summary="Rechercher un compte par numéro",
     *     description="Recherche un compte par son numéro. Si le compte est archivé (Épargne), il est récupéré depuis Neon.",
     *     @OA\Parameter(
     *         name="numero",
     *         in="path",
     *         description="Le numéro du compte à rechercher",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Compte trouvé",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="string", example="550e8400-e29b-41d4-a716-446655440000"),
     *                 @OA\Property(property="numeroCompte", type="string", example="C00123456"),
     *                 @OA\Property(property="titulaire", type="string", example="Amadou Diallo"),
     *                 @OA\Property(property="type", type="string", example="epargne"),
     *                 @OA\Property(property="solde", type="number", example=1250000),
     *                 @OA\Property(property="statut", type="string", example="actif"),
     *                 @OA\Property(property="dateBlocage", type="string", format="date-time", example="2025-10-29T12:52:24.000000Z", nullable=true),
     *                 @OA\Property(property="dateDeblocagePrevue", type="string", format="date-time", example="2025-11-28T12:52:24.000000Z", nullable=true),
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
     *                 @OA\Property(property="message", type="string", example="Le compte avec le numéro spécifié n'existe pas")
     *             )
     *         )
     *     )
     * )
     */
    public function rechercheParNumero(string $numero)
    {
        // Pour le moment, sans authentification, traiter comme admin
        $isAdmin = true;
        $clientId = null;

        // Use service to find account by numero
        $compte = $this->compteService->findCompteByNumero($numero, $clientId);

        if (!$compte) {
            throw new CompteNotFoundException();
        }

        // Load client relationship for the response
        $compte->load('client');

        return $this->successResponse(new CompteResource($compte));
    }

    /**
     * @OA\Get(
     *     path="/ndiaye/v1/comptes/recherche/cni/{cni}",
     *     summary="Rechercher un compte par CNI",
     *     description="Recherche un compte par le CNI du client. Si le compte est archivé (Épargne), il est récupéré depuis Neon.",
     *     @OA\Parameter(
     *         name="cni",
     *         in="path",
     *         description="Le CNI du client",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Compte trouvé",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="string", example="550e8400-e29b-41d4-a716-446655440000"),
     *                 @OA\Property(property="numeroCompte", type="string", example="C00123456"),
     *                 @OA\Property(property="titulaire", type="string", example="Amadou Diallo"),
     *                 @OA\Property(property="type", type="string", example="epargne"),
     *                 @OA\Property(property="solde", type="number", example=1250000),
     *                 @OA\Property(property="statut", type="string", example="actif"),
     *                 @OA\Property(property="dateBlocage", type="string", format="date-time", example="2025-10-29T12:52:24.000000Z", nullable=true),
     *                 @OA\Property(property="dateDeblocagePrevue", type="string", format="date-time", example="2025-11-28T12:52:24.000000Z", nullable=true),
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
     *                 @OA\Property(property="message", type="string", example="Le compte avec le CNI spécifié n'existe pas")
     *             )
     *         )
     *     )
     * )
     */
    public function rechercheParCni(string $cni)
    {
        // Pour le moment, sans authentification, traiter comme admin
        $isAdmin = true;
        $clientId = null;

        // Use service to find account by CNI
        $compte = $this->compteService->findCompteByCni($cni, $clientId);

        if (!$compte) {
            throw new CompteNotFoundException();
        }

        // Load client relationship for the response
        $compte->load('client');

        return $this->successResponse(new CompteResource($compte));
    }
}
