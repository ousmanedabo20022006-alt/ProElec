<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interventions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('entreprise_id')
                ->constrained('entreprises')
                ->cascadeOnDelete();

            $table->foreignId('client_id')
                ->constrained('clients')
                ->cascadeOnDelete();

            $table->string('titre');
            $table->text('description')->nullable();
            $table->string('adresse')->nullable();
            $table->string('code_postal', 10)->nullable();
            $table->string('ville')->nullable();
            $table->dateTime('date_prevue')->nullable();
            $table->string('statut')->default('planifiee');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interventions');
    }
};