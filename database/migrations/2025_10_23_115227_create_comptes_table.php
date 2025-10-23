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
            $table->bigIncrements('id');
            $table->string('numeroCompte');
            $table->string('titulaire');
            $table->enum('type', ['Epargne', 'Cheque']);
            $table->string('devise')->default('FCFA');
            $table->date('dateCreation');
            $table->enum('statut', ['Actif', 'Bloque', 'Ferme', 'Supprime'])->default('Actif');
            $table->json('metadata')->nullable();
            $table->unsignedBigInteger('client_id');
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
