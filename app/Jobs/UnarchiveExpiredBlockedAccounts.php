<?php

namespace App\Jobs;

use App\Models\Compte;
use App\Models\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UnarchiveExpiredBlockedAccounts implements ShouldQueue
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
            // Unarchive the account (set back to active)
            $account->update([
                'statut' => 'Actif',
                'motifBlocage' => null,
                'date_debut_blocage' => null,
                'date_fin_blocage' => null
            ]);

            // Unarchive all transactions for this account (set back to 'Validee')
            Transaction::where('compte_id', $account->id)
                ->where('statut', 'Archivee')
                ->update(['statut' => 'Validee']);
        }
    }
}
