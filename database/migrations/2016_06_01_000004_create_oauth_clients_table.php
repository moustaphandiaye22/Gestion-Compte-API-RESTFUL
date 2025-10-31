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
        Schema::create('oauth_clients', function (Blueprint $table) {
            $table->bigIncrements('id');
            // If users are UUIDs, store the owner id as string
            $table->string('user_id', 36)->nullable()->index();
            $table->string('name');
            $table->string('secret', 100)->nullable();
            $table->string('provider')->nullable();
            $table->text('redirect');
            // Use smallInteger for better compatibility with Postgres prepared statements
            // Passport may insert 1/0 as integers; keeping boolean here caused a type mismatch
            $table->smallInteger('personal_access_client')->default(0);
            $table->smallInteger('password_client')->default(0);
            $table->smallInteger('revoked')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('oauth_clients');
    }
};
