<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();

            // Entreprise ProElec propriétaire du client
            $table->foreignId('entreprise_id')
                ->constrained('entreprises')
                ->cascadeOnDelete();

            // Informations du client
            $table->string('nom');
            $table->string('prenom')->nullable();

            $table->string('email')->nullable();
            $table->string('telephone')->nullable();

            // Adresse du client
            $table->string('adresse')->nullable();
            $table->string('code_postal', 10)->nullable();
            $table->string('ville')->nullable();

            // Informations complémentaires
            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};