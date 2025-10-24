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
         Schema::create('transactions', function (Blueprint $table) {
              $table->string('id', 36)->primary();
              $table->string('numeroCompte');
              $table->enum('type', ['Depot', 'Retrait', 'Virement']);
              $table->decimal('montant', 15, 2);
              $table->datetime('dateTransaction');
              $table->text('description')->nullable();
              $table->enum('statut', ['En attente', 'Validee', 'Annulee'])->default('En attente');
              $table->string('compte_id', 36);
              $table->foreign('compte_id')->references('id')->on('comptes');
              $table->timestamps();

          });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
