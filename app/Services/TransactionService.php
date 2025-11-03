<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\Compte;

/**
 * Service pour la gestion des transactions
 * Responsable de toutes les opérations liées aux transactions
 */
class TransactionService
{
    /**
     * Récupère les transactions d'un compte spécifique
     */
    public function getAccountTransactions(string $compteId, ?string $clientId = null, int $limit = 10): array
    {
        // Vérifier l'accès au compte
        $compte = Compte::find($compteId);
        if (!$compte || ($clientId && $compte->client_id !== $clientId)) {
            return ['error' => 'Accès non autorisé au compte'];
        }

        $transactions = Transaction::where('compte_id', $compteId)
            ->where('statut', 'Validee')
            ->orderBy('dateTransaction', 'desc')
            ->paginate($limit);

        $formattedTransactions = $transactions->getCollection()->map(function ($transaction) {
            return [
                'id' => $transaction->id,
                'numeroCompte' => $transaction->numeroCompte,
                'type' => $transaction->type,
                'montant' => $transaction->montant,
                'dateTransaction' => $transaction->dateTransaction,
                'description' => $transaction->description,
                'statut' => $transaction->statut,
            ];
        });

        return [
            'transactions' => $formattedTransactions,
            'pagination' => [
                'currentPage' => $transactions->currentPage(),
                'totalPages' => $transactions->lastPage(),
                'totalItems' => $transactions->total(),
                'itemsPerPage' => $transactions->perPage(),
                'hasNext' => $transactions->hasMorePages(),
                'hasPrevious' => $transactions->currentPage() > 1,
            ],
            'links' => [
                'self' => $transactions->url($transactions->currentPage()),
                'next' => $transactions->nextPageUrl(),
                'previous' => $transactions->previousPageUrl(),
                'first' => $transactions->url(1),
                'last' => $transactions->url($transactions->lastPage()),
            ]
        ];
    }

    /**
     * Récupère les statistiques d'un compte spécifique
     */
    public function getAccountStatistics(string $compteId, ?string $clientId = null): array
    {
        // Vérifier l'accès au compte
        $compte = Compte::find($compteId);
        if (!$compte || ($clientId && $compte->client_id !== $clientId)) {
            return ['error' => 'Accès non autorisé au compte'];
        }

        // Calculs des statistiques
        $totalDepot = Transaction::where('compte_id', $compteId)
            ->where('type', 'Depot')
            ->where('statut', 'Validee')
            ->sum('montant');

        $totalRetrait = Transaction::where('compte_id', $compteId)
            ->where('type', 'Retrait')
            ->where('statut', 'Validee')
            ->sum('montant');

        $totalTransfert = Transaction::where('compte_id', $compteId)
            ->where('type', 'Transfert')
            ->where('statut', 'Validee')
            ->sum('montant');

        $nombreTransactions = Transaction::where('compte_id', $compteId)
            ->where('statut', 'Validee')
            ->count();

        $solde = $totalDepot - $totalRetrait - $totalTransfert;

        // Dernière transaction
        $derniereTransaction = Transaction::where('compte_id', $compteId)
            ->where('statut', 'Validee')
            ->orderBy('dateTransaction', 'desc')
            ->first();

        return [
            'totalDepot' => $totalDepot,
            'totalRetrait' => $totalRetrait,
            'totalTransfert' => $totalTransfert,
            'nombreTransactions' => $nombreTransactions,
            'solde' => $solde,
            'derniereTransaction' => $derniereTransaction ? [
                'id' => $derniereTransaction->id,
                'type' => $derniereTransaction->type,
                'montant' => $derniereTransaction->montant,
                'dateTransaction' => $derniereTransaction->dateTransaction,
                'description' => $derniereTransaction->description,
            ] : null,
        ];
    }

    /**
     * Récupère les dernières transactions globales (pour le dashboard)
     */
    public function getLatestTransactions(int $limit = 10): array
    {
        return Transaction::with('compte')
            ->where('statut', 'Validee')
            ->orderBy('dateTransaction', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($transaction) {
                return [
                    'id' => $transaction->id,
                    'numeroCompte' => $transaction->numeroCompte,
                    'type' => $transaction->type,
                    'montant' => $transaction->montant,
                    'dateTransaction' => $transaction->dateTransaction,
                    'description' => $transaction->description,
                ];
            })
            ->toArray();
    }

    /**
     * Récupère les dernières transactions d'un client
     */
    public function getClientLatestTransactions(string $clientId, int $limit = 10): array
    {
        return Transaction::with('compte')
            ->whereHas('compte', function ($query) use ($clientId) {
                $query->where('client_id', $clientId);
            })
            ->where('statut', 'Validee')
            ->orderBy('dateTransaction', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($transaction) {
                return [
                    'id' => $transaction->id,
                    'numeroCompte' => $transaction->numeroCompte,
                    'type' => $transaction->type,
                    'montant' => $transaction->montant,
                    'dateTransaction' => $transaction->dateTransaction,
                    'description' => $transaction->description,
                ];
            })
            ->toArray();
    }

    /**
     * Compte le nombre total de transactions validées
     */
    public function countValidatedTransactions(?string $clientId = null): int
    {
        $query = Transaction::where('statut', 'Validee');

        if ($clientId) {
            $query->whereHas('compte', function ($subQuery) use ($clientId) {
                $subQuery->where('client_id', $clientId);
            });
        }

        return $query->count();
    }

    /**
     * Calcule le total des montants par type de transaction
     */
    public function calculateTransactionTotals(?string $clientId = null): array
    {
        $baseQuery = Transaction::where('statut', 'Validee');

        if ($clientId) {
            $baseQuery->whereHas('compte', function ($query) use ($clientId) {
                $query->where('client_id', $clientId);
            });
        }

        return [
            'depot' => (clone $baseQuery)->where('type', 'Depot')->sum('montant'),
            'retrait' => (clone $baseQuery)->where('type', 'Retrait')->sum('montant'),
            'transfert' => (clone $baseQuery)->where('type', 'Transfert')->sum('montant'),
        ];
    }
}