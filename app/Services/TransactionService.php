<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\Compte;
use App\Services\SmsServiceInterface;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * Service pour la gestion des transactions
 * Responsable de toutes les opérations liées aux transactions
 */
class TransactionService
{
    protected SmsServiceInterface $smsService;

    public function __construct(SmsServiceInterface $smsService)
    {
        $this->smsService = $smsService;
    }
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

    /**
     * Envoie une notification après une transaction
     */
    public function sendTransactionNotification(Transaction $transaction): void
    {
        $compte = $transaction->compte;
        $client = $compte->client;

        // Message de notification
        $message = $this->buildTransactionMessage($transaction, $compte);

        // Envoi par SMS
        try {
            $this->smsService->send($client->telephone, $message);
        } catch (\Exception $e) {
            // Log l'erreur mais ne bloque pas la transaction
            Log::error('Erreur envoi SMS notification transaction: ' . $e->getMessage());
        }

        // Envoi par email
        try {
            $this->sendTransactionEmail($client, $transaction, $compte);
        } catch (\Exception $e) {
            // Log l'erreur mais ne bloque pas la transaction
            Log::error('Erreur envoi email notification transaction: ' . $e->getMessage());
        }
    }

    /**
     * Construit le message de notification pour la transaction
     */
    private function buildTransactionMessage(Transaction $transaction, Compte $compte): string
    {
        $type = $this->getTransactionTypeLabel($transaction->type);
        $montant = number_format($transaction->montant, 0, ',', ' ') . ' FCFA';
        $numeroCompte = $compte->numeroCompte;

        return "Transaction effectuée sur le compte {$numeroCompte}:\n" .
               "Type: {$type}\n" .
               "Montant: {$montant}\n" .
               "Date: " . $transaction->dateTransaction->format('d/m/Y H:i') . "\n" .
               "Description: {$transaction->description}\n" .
               "Nouveau solde: " . number_format($compte->solde, 0, ',', ' ') . " FCFA";
    }

    /**
     * Envoie un email de notification de transaction
     */
    private function sendTransactionEmail($client, Transaction $transaction, Compte $compte): void
    {
        $details = [
            'client' => $client,
            'transaction' => $transaction,
            'compte' => $compte,
            'typeLabel' => $this->getTransactionTypeLabel($transaction->type),
        ];

        Mail::send('emails.transaction_notification', $details, function ($message) use ($client) {
            $message->to($client->email, $client->prenom . ' ' . $client->nom)
                    ->subject('Notification de transaction - Gestion Compte');
        });
    }

    /**
     * Retourne le libellé du type de transaction
     */
    private function getTransactionTypeLabel(string $type): string
    {
        return match ($type) {
            'Depot' => 'Dépôt',
            'Retrait' => 'Retrait',
            'Transfert' => 'Transfert',
            default => $type,
        };
    }

    /**
     * Archive les transactions de la semaine dans MongoDB
     */
    public function archiveWeeklyTransactions(): void
    {
        $startOfWeek = now()->startOfWeek();
        $endOfWeek = now()->endOfWeek();

        // Récupérer toutes les transactions de la semaine
        $transactions = Transaction::with(['compte.client'])
            ->whereBetween('dateTransaction', [$startOfWeek, $endOfWeek])
            ->where('statut', 'Validee')
            ->get();

        if ($transactions->isEmpty()) {
            Log::info('Aucune transaction à archiver pour cette semaine');
            return;
        }

        // Créer le nom de la collection pour cette semaine
        $weekNumber = $startOfWeek->format('Y-W');
        $collectionName = "transactions_semaine_{$weekNumber}";

        // Préparer les données pour MongoDB
        $archivedTransactions = $transactions->map(function ($transaction) {
            return [
                'id' => $transaction->id,
                'numeroCompte' => $transaction->numeroCompte,
                'type' => $transaction->type,
                'montant' => $transaction->montant,
                'dateTransaction' => $transaction->dateTransaction,
                'description' => $transaction->description,
                'statut' => $transaction->statut,
                'compte_id' => $transaction->compte_id,
                'compte' => [
                    'numeroCompte' => $transaction->compte->numeroCompte,
                    'titulaire' => $transaction->compte->titulaire,
                    'type' => $transaction->compte->type,
                    'devise' => $transaction->compte->devise,
                ],
                'client' => [
                    'id' => $transaction->compte->client->id,
                    'prenom' => $transaction->compte->client->prenom,
                    'nom' => $transaction->compte->client->nom,
                    'email' => $transaction->compte->client->email,
                    'telephone' => $transaction->compte->client->telephone,
                ],
                'dateArchivage' => now(),
                'semaine' => $weekNumber,
            ];
        });

        try {
            // Insérer dans MongoDB
            $collection = DB::connection('mongodb')->collection($collectionName);
            $collection->insertMany($archivedTransactions->toArray());

            // Marquer les transactions comme archivées dans PostgreSQL
            Transaction::whereIn('id', $transactions->pluck('id'))
                ->update(['statut' => 'Archivee']);

            Log::info("Archivage réussi: {$transactions->count()} transactions archivées dans la collection {$collectionName}");

        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'archivage des transactions: ' . $e->getMessage());
            throw $e;
        }
    }
}