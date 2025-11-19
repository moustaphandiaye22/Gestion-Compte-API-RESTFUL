<?php

namespace App\Jobs;

use App\Services\TransactionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ArchiveWeeklyTransactions implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(TransactionService $transactionService): void
    {
        try {
            Log::info('Début de l\'archivage hebdomadaire des transactions');

            $transactionService->archiveWeeklyTransactions();

            Log::info('Archivage hebdomadaire des transactions terminé avec succès');

        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'archivage hebdomadaire des transactions: ' . $e->getMessage());
            throw $e;
        }
    }
}
