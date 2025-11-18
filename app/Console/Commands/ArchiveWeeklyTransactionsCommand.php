<?php

namespace App\Console\Commands;

use App\Services\TransactionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ArchiveWeeklyTransactionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'transactions:archive-weekly {--force : Force l\'archivage même si ce n\'est pas dimanche}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Archive hebdomadairement les transactions dans MongoDB';

    protected TransactionService $transactionService;

    public function __construct(TransactionService $transactionService)
    {
        parent::__construct();
        $this->transactionService = $transactionService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Début de l\'archivage hebdomadaire des transactions...');

        // Vérifier si c'est dimanche ou si --force est utilisé
        if (!$this->option('force') && now()->dayOfWeek !== 0) {
            $this->warn('Cette commande ne peut être exécutée que le dimanche. Utilisez --force pour forcer l\'exécution.');
            return Command::FAILURE;
        }

        try {
            $this->transactionService->archiveWeeklyTransactions();

            $this->info('Archivage hebdomadaire des transactions terminé avec succès.');
            Log::info('Archivage hebdomadaire des transactions exécuté manuellement');

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Erreur lors de l\'archivage: ' . $e->getMessage());
            Log::error('Erreur lors de l\'archivage hebdomadaire des transactions: ' . $e->getMessage());

            return Command::FAILURE;
        }
    }
}
