<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('numeroCompte');
            $table->index('numeroCompte');
            $table->enum('type', ['Depot', 'Retrait', 'Virement']);
            $table->index('type');
            $table->decimal('montant', 15, 2);
            $table->datetime('dateTransaction');
            $table->text('description')->nullable();
            $table->enum('statut', ['En attente', 'Validee', 'Annulee'])->default('En attente');
            $table->index('statut');
            $table->uuid('compte_id');
            $table->foreign('compte_id')->references('id')->on('comptes')->onDelete('cascade');
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
