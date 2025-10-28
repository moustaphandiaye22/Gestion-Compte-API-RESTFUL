<?php

namespace App\Jobs;

use App\Models\Compte;
use App\Models\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ArchiveExpiredBlockedAccounts implements ShouldQueue
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
    public function handle(): void
    {
        // Find accounts where blocking end date has expired
        $expiredAccounts = Compte::where('statut', 'Bloque')
            ->where('date_fin_blocage', '<=', now())
            ->get();

        foreach ($expiredAccounts as $account) {
            // Archive the account
            $account->update(['statut' => 'Supprime']);

            // Archive all transactions for this account
            Transaction::where('compte_id', $account->id)
                ->update(['statut' => 'Archivee']);
        }
    }
}
