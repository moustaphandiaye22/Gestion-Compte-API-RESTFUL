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
         Schema::create('comptes', function (Blueprint $table) {
              $table->string('id', 36)->primary();
              $table->string('numeroCompte')->unique();
              $table->string('titulaire');
              $table->enum('type', ['Epargne', 'Cheque']);
              $table->string('devise')->default('FCFA');
              $table->date('dateCreation');
              $table->enum('statut', ['Actif', 'Bloque', 'Ferme', 'Supprime'])->default('Actif');
              $table->json('metadata')->nullable();
              $table->string('client_id', 36);
              $table->foreign('client_id')->references('id')->on('clients');
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
