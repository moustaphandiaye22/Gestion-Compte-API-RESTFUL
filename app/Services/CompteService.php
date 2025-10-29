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
            ->where('comptes.statut', 'Actif');

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
            $query->whereRaw('(SELECT COALESCE(SUM(CASE WHEN type = \'Depot\' THEN montant ELSE -montant END), 0) FROM transactions WHERE transactions.compte_id = comptes.id) >= ?', [$request->solde_min]);
        }

        if ($request->filled('solde_max')) {
            $query->whereRaw('(SELECT COALESCE(SUM(CASE WHEN type = \'Depot\' THEN montant ELSE -montant END), 0) FROM transactions WHERE transactions.compte_id = comptes.id) <= ?', [$request->solde_max]);
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
      * Récupère un compte spécifique par ID avec stratégie de recherche
      */
    public function findCompteById(string $id, ?string $clientId = null): ?Compte
    {
        // First, try to find in local database for active cheque or epargne accounts
        $compte = Compte::where('id', $id)
            ->whereIn('type', ['Cheque', 'Epargne'])
            ->where('statut', 'Actif')
            ->first();

        if ($compte) {
            // Check authorization if clientId is provided
            if ($clientId && $compte->client_id !== $clientId) {
                return null; // Client can only access their own accounts
            }
            return $compte;
        }

        // If not found locally, try serverless (placeholder)
        return $this->findInServerless($id, $clientId);
    }

    /**
      * Placeholder for serverless search
      */
    private function findInServerless(string $id, ?string $clientId = null): ?Compte
    {
        // In real implementation, make HTTP call to serverless API
        // For example: Http::get("https://serverless-api.example.com/comptes/{$id}")
        // Then map to Compte model or return null

        // For demo, return null
        return null;
    }

    /**
       * Recherche un compte par numéro
       */
    public function findCompteByNumero(string $numero, ?string $clientId = null): ?Compte
    {
        // Recherche d'abord dans les comptes actifs
        $compte = Compte::numero($numero)->first();

        if ($compte) {
            // Check authorization if clientId is provided
            if ($clientId && $compte->client_id !== $clientId) {
                return null; // Client can only access their own accounts
            }
            return $compte;
        }

        // Si non trouvé et que c'est un compte Épargne, chercher dans Neon (archivé)
        if (str_starts_with($numero, 'CPT-') && $this->isEpargneAccount($numero)) {
            return $this->findInNeon($numero, $clientId);
        }

        return null;
    }

    /**
       * Recherche un compte par CNI du client
       */
    public function findCompteByCni(string $cni, ?string $clientId = null): ?Compte
    {
        // Recherche d'abord dans les comptes actifs
        $compte = Compte::whereHas('client', function ($query) use ($cni) {
            $query->where('cni', $cni);
        })->first();

        if ($compte) {
            // Check authorization if clientId is provided
            if ($clientId && $compte->client_id !== $clientId) {
                return null; // Client can only access their own accounts
            }
            return $compte;
        }

        // Si non trouvé, chercher dans Neon pour comptes Épargne archivés
        return $this->findInNeonByCni($cni, $clientId);
    }

    /**
       * Vérifie si un numéro de compte correspond à un compte Épargne
       */
    private function isEpargneAccount(string $numero): bool
    {
        // Logique simplifiée : on considère que tous les comptes peuvent être Épargne
        // En production, on pourrait avoir une logique plus sophistiquée
        return true;
    }

    /**
       * Recherche dans Neon (base de données cloud pour comptes archivés)
       */
    private function findInNeon(string $numero, ?string $clientId = null): ?Compte
    {
        // Simulation de l'appel à Neon
        // En production : Http::get("https://neon-api.example.com/comptes/numero/{$numero}")

        // Pour la démo, retourner null (pas de compte archivé)
        return null;
    }

    /**
       * Recherche dans Neon par CNI
       */
    private function findInNeonByCni(string $cni, ?string $clientId = null): ?Compte
    {
        // Simulation de l'appel à Neon
        // En production : Http::get("https://neon-api.example.com/comptes/cni/{$cni}")

        // Pour la démo, retourner null (pas de compte archivé)
        return null;
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
