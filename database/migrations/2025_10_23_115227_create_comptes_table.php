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
        Schema::create('comptes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('numeroCompte')->unique();
            $table->index('numeroCompte');
            $table->string('titulaire');
            $table->enum('type', ['Epargne', 'Cheque']);
            $table->string('devise')->default('XOF');
            $table->date('dateCreation');
            $table->enum('statut', ['Actif', 'Bloque', 'Ferme'])->default('Actif');
            $table->index('statut');
            $table->json('metadata')->nullable();
            $table->uuid('client_id');
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comptes');
    }
};
