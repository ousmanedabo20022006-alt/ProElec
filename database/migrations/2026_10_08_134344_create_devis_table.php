<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('devis', function (Blueprint $table) {
            $table->id();

            // Entreprise ProElec propriétaire du devis
            $table->foreignId('entreprise_id')
                ->constrained('entreprises')
                ->cascadeOnDelete();

            // Client concerné par le devis
            $table->foreignId('client_id')
                ->constrained('clients')
                ->cascadeOnDelete();

            // Intervention concernée, facultative
            $table->foreignId('intervention_id')
                ->nullable()
                ->constrained('interventions')
                ->nullOnDelete();

            // Identification du devis
            $table->string('numero')->unique();

            // Dates
            $table->date('date_emission');
            $table->date('date_validite')->nullable();

            // Statut
            $table->string('statut')->default('brouillon');

            // Montants
            $table->decimal('montant_ht', 10, 2)->default(0);
            $table->decimal('taux_tva', 5, 2)->default(20);
            $table->decimal('montant_tva', 10, 2)->default(0);
            $table->decimal('montant_ttc', 10, 2)->default(0);

            // Informations complémentaires
            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('devis');
    }
};