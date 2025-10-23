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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
     * Lister tous les comptes
     *
     * Liste tous les comptes avec filtrage, tri et pagination.
     * Admin peut voir tous les comptes, Client ne voit que ses comptes.
     * Seuls les comptes non supprimés, de type cheque ou epargne, et actifs sont retournés.
     *
     * @queryParam page int Numéro de page (default: 1)
     * @queryParam limit int Nombre d'éléments par page (default: 10, max: 100)
     * @queryParam type string Filtrer par type (epargne, cheque)
     * @queryParam statut string Filtrer par statut (actif, bloque, ferme)
     * @queryParam search string Recherche par titulaire ou numéro
     * @queryParam sort string Tri (dateCreation, solde, titulaire)
     * @queryParam order string Ordre (asc, desc)
     *
     * @response 200 {
     *   "success": true,
     *   "data": [
     *     {
     *       "id": "550e8400-e29b-41d4-a716-446655440000",
     *       "numeroCompte": "C00123456",
     *       "titulaire": "Amadou Diallo",
     *       "type": "epargne",
     *       "solde": 1250000,
     *       "devise": "FCFA",
     *       "dateCreation": "2023-03-15T00:00:00Z",
     *       "statut": "bloque",
     *       "motifBlocage": "Inactivité de 30+ jours",
     *       "metadata": {
     *         "derniereModification": "2023-06-10T14:30:00Z",
     *         "version": 1
     *       }
     *     }
     *   ],
     *   "pagination": {
     *     "currentPage": 1,
     *     "totalPages": 3,
     *     "totalItems": 25,
     *     "itemsPerPage": 10,
     *     "hasNext": true,
     *     "hasPrevious": false
     *   },
     *   "links": {
     *     "self": "/api/v1/comptes?page=1&limit=10",
     *     "next": "/api/v1/comptes?page=2&limit=10",
     *     "first": "/api/v1/comptes?page=1&limit=10",
     *     "last": "/api/v1/comptes?page=3&limit=10"
     *   }
     * }
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
     * Afficher un compte spécifique
     */
    public function show(string $id)
    {
        
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
     * Récupérer les comptes archivés
     *
     * Liste tous les comptes archivés (statut 'Supprime') avec pagination.
     * Accessible uniquement aux administrateurs.
     *
     * @queryParam page int Numéro de page (default: 1)
     * @queryParam limit int Nombre d'éléments par page (default: 10, max: 100)
     *
     * @response 200 {
     *   "success": true,
     *   "data": [...],
     *   "pagination": {...},
     *   "links": {...}
     * }
     */
    public function archives()
    {
        $comptes = Compte::withoutGlobalScope('nonSupprime')
            ->with('client')
            ->where('statut', 'Supprime')
            ->where('type', 'Epargne')
            ->paginate(request('limit', 10));

        return new CompteCollection($comptes);
    }
}
