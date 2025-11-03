<?php

namespace App\Services;

use App\Models\Compte;
use App\Models\Transaction;

/**
 * Service pour la gestion du tableau de bord
 * Responsable des statistiques et métriques du dashboard
 */
class DashboardService
{
    /**
     * Récupère les statistiques du dashboard pour admin
     */
    public function getAdminDashboardStats(): array
    {
        // Total comptes actifs
        $totalComptes = Compte::where('statut', '!=', 'Supprime')->count();

        // Balance totale
        $balanceTotale = $this->calculateTotalBalance();

        // Nombre total de transactions validées
        $nombreTransactions = Transaction::where('statut', 'Validee')->count();

        // Dernières 10 transactions
        $dernieresTransactions = Transaction::with('compte')
            ->where('statut', 'Validee')
            ->orderBy('dateTransaction', 'desc')
            ->limit(10)
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
            });

        // Comptes créés aujourd'hui
        $comptesCreesAujourdhui = Compte::with('client')
            ->where('statut', '!=', 'Supprime')
            ->whereDate('dateCreation', today())
            ->orderBy('dateCreation', 'desc')
            ->get()
            ->map(function ($compte) {
                return [
                    'id' => $compte->id,
                    'numeroCompte' => $compte->numeroCompte,
                    'titulaire' => $compte->titulaire,
                    'type' => $compte->type,
                    'dateCreation' => $compte->dateCreation,
                ];
            });

        return [
            'totalComptes' => $totalComptes,
            'balanceTotale' => $balanceTotale,
            'nombreTransactions' => $nombreTransactions,
            'dernieresTransactions' => $dernieresTransactions,
            'comptesCreesAujourdhui' => $comptesCreesAujourdhui,
        ];
    }

    /**
     * Récupère les statistiques du dashboard pour client
     */
    public function getClientDashboardStats(string $clientId): array
    {
        // Nombre de comptes du client
        $nombreComptes = Compte::where('client_id', $clientId)
            ->where('statut', '!=', 'Supprime')
            ->count();

        // Balance totale du client
        $balanceTotale = $this->calculateClientBalance($clientId);

        // Nombre total de transactions du client
        $nombreTransactions = Transaction::whereHas('compte', function ($query) use ($clientId) {
            $query->where('client_id', $clientId);
        })->where('statut', 'Validee')->count();

        // Dernières 10 transactions du client
        $dernieresTransactions = Transaction::with('compte')
            ->whereHas('compte', function ($query) use ($clientId) {
                $query->where('client_id', $clientId);
            })
            ->where('statut', 'Validee')
            ->orderBy('dateTransaction', 'desc')
            ->limit(10)
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
            });

        // Liste des comptes du client
        $comptes = Compte::where('client_id', $clientId)
            ->where('statut', '!=', 'Supprime')
            ->orderBy('dateCreation', 'desc')
            ->get()
            ->map(function ($compte) {
                return [
                    'id' => $compte->id,
                    'numeroCompte' => $compte->numeroCompte,
                    'titulaire' => $compte->titulaire,
                    'type' => $compte->type,
                    'solde' => $compte->solde,
                    'dateCreation' => $compte->dateCreation,
                ];
            });

        return [
            'nombreComptes' => $nombreComptes,
            'balanceTotale' => $balanceTotale,
            'nombreTransactions' => $nombreTransactions,
            'dernieresTransactions' => $dernieresTransactions,
            'comptes' => $comptes,
        ];
    }

    /**
     * Calcule la balance totale de tous les comptes
     */
    private function calculateTotalBalance(): float
    {
        $depotTotal = Transaction::where('type', 'Depot')
            ->where('statut', 'Validee')
            ->sum('montant');

        $retraitTotal = Transaction::where('type', 'Retrait')
            ->where('statut', 'Validee')
            ->sum('montant');

        $transfertSortantTotal = Transaction::where('type', 'Transfert')
            ->where('statut', 'Validee')
            ->sum('montant');

        return $depotTotal - $retraitTotal - $transfertSortantTotal;
    }

    /**
     * Calcule la balance totale pour un client spécifique
     */
    private function calculateClientBalance(string $clientId): float
    {
        $depotTotal = Transaction::whereHas('compte', function ($query) use ($clientId) {
            $query->where('client_id', $clientId);
        })->where('type', 'Depot')->where('statut', 'Validee')->sum('montant');

        $retraitTotal = Transaction::whereHas('compte', function ($query) use ($clientId) {
            $query->where('client_id', $clientId);
        })->where('type', 'Retrait')->where('statut', 'Validee')->sum('montant');

        $transfertSortantTotal = Transaction::whereHas('compte', function ($query) use ($clientId) {
            $query->where('client_id', $clientId);
        })->where('type', 'Transfert')->where('statut', 'Validee')->sum('montant');

        return $depotTotal - $retraitTotal - $transfertSortantTotal;
    }
}