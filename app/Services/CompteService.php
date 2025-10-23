<?php

namespace App\Services;

use App\Models\Compte;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Service pour la gestion des comptes bancaires
 * Centralise la logique de filtrage, tri et pagination
 */
class CompteService
{
    /**
     * Applique les filtres, tri et pagination à la requête des comptes
     */
    public function getComptesWithFilters(Request $request, ?string $clientId = null): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = Compte::query()
            ->with('client')
            ->whereIn('comptes.type', ['Epargne', 'Cheque'])
            ->whereIn('comptes.statut', ['Actif', 'Bloque', 'Ferme']);

        // Filtrage par client si spécifié
        if ($clientId) {
            $query->where('client_id', $clientId);
        }

        // Appliquer les filtres
        $query = $this->applyFilters($query, $request);

        // Appliquer le tri
        $query = $this->applySorting($query, $request);

        // Appliquer la pagination
        return $this->applyPagination($query, $request);
    }

    /**
     * Applique les filtres à la requête
     */
    protected function applyFilters(Builder $query, Request $request): Builder
    {
        // Filtre par type
        if ($request->filled('type')) {
            $query->where('comptes.type', ucfirst(strtolower($request->type)));
        }

        // Filtre par statut
        if ($request->filled('statut')) {
            $query->where('comptes.statut', ucfirst(strtolower($request->statut)));
        }

        // Recherche par titulaire ou numéro
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('comptes.titulaire', 'ILIKE', "%{$search}%")
                  ->orWhere('comptes.numeroCompte', 'ILIKE', "%{$search}%");
            });
        }

        // Filtre par devise
        if ($request->filled('devise')) {
            $query->where('comptes.devise', $request->devise);
        }

        // Filtre par plage de dates
        if ($request->filled('date_from')) {
            $query->whereDate('comptes.dateCreation', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('comptes.dateCreation', '<=', $request->date_to);
        }

        // Filtre par solde minimum/maximum
        if ($request->filled('solde_min')) {
            $query->whereHas('transactions', function ($q) use ($request) {
                $q->havingRaw('SUM(CASE WHEN type = \'Depot\' THEN montant ELSE -montant END) >= ?', [$request->solde_min]);
            });
        }

        if ($request->filled('solde_max')) {
            $query->whereHas('transactions', function ($q) use ($request) {
                $q->havingRaw('SUM(CASE WHEN type = \'Depot\' THEN montant ELSE -montant END) <= ?', [$request->solde_max]);
            });
        }

        return $query;
    }

    /**
     * Applique le tri à la requête
     */
    protected function applySorting(Builder $query, Request $request): Builder
    {
        $sortBy = $request->get('sort', 'dateCreation');
        $order = $request->get('order', 'desc');

        // Champs de tri autorisés
        $allowedSortFields = [
            'dateCreation',
            'solde',
            'titulaire',
            'numeroCompte',
            'type',
            'statut',
            'devise'
        ];

        if (!in_array($sortBy, $allowedSortFields)) {
            $sortBy = 'dateCreation';
        }

        if (!in_array($order, ['asc', 'desc'])) {
            $order = 'desc';
        }

        // Tri spécial pour le solde (calculé)
        if ($sortBy === 'solde') {
            $query->leftJoin('transactions', 'comptes.id', '=', 'transactions.compte_id')
                  ->selectRaw('comptes.*, COALESCE(SUM(CASE WHEN transactions.type = \'Depot\' THEN transactions.montant ELSE -transactions.montant END), 0) as solde_calcule')
                  ->groupBy('comptes.id')
                  ->orderBy('solde_calcule', $order);
        } else {
            $query->orderBy($sortBy, $order);
        }

        return $query;
    }

    /**
     * Applique la pagination à la requête
     */
    protected function applyPagination(Builder $query, Request $request): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $perPage = $request->get('limit', 10);

        // Limiter le nombre d'éléments par page
        if ($perPage < 1 || $perPage > 100) {
            $perPage = 10;
        }

        return $query->paginate($perPage);
    }

    /**
     * Valide les paramètres de requête pour les comptes
     * Note: Cette méthode est maintenant gérée par ListComptesRequest
     * Gardée pour compatibilité future si nécessaire
     */
    public function validateQueryParameters(Request $request): array
    {
        return $request->all(); // Les validations sont maintenant dans ListComptesRequest
    }

    /**
     * Récupère les statistiques des comptes
     */
    public function getComptesStats(?string $clientId = null): array
    {
        $query = Compte::query();

        if ($clientId) {
            $query->where('client_id', $clientId);
        }

        return [
            'total' => $query->count(),
            'actifs' => (clone $query)->where('statut', 'actif')->count(),
            'bloques' => (clone $query)->where('statut', 'bloque')->count(),
            'fermes' => (clone $query)->where('statut', 'ferme')->count(),
            'epargne' => (clone $query)->where('type', 'Epargne')->count(),
            'cheque' => (clone $query)->where('type', 'Cheque')->count(),
        ];
    }
}
