<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE transactions DROP CONSTRAINT transactions_statut_check");
        DB::statement("ALTER TABLE transactions ADD CONSTRAINT transactions_statut_check CHECK (statut IN ('En attente', 'Validee', 'Annulee', 'Archivee'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE transactions DROP CONSTRAINT transactions_statut_check");
        DB::statement("ALTER TABLE transactions ADD CONSTRAINT transactions_statut_check CHECK (statut IN ('En attente', 'Validee', 'Annulee'))");
    }
};
