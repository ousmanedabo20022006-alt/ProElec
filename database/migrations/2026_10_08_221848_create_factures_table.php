
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('factures', function (Blueprint $table) {
            $table->id();

            // Entreprise propriétaire de la facture
            $table->foreignId('entreprise_id')
                ->constrained('entreprises')
                ->cascadeOnDelete();

            // Client concerné par la facture
            $table->foreignId('client_id')
                ->constrained('clients')
                ->restrictOnDelete();

            // Devis d'origine, facultatif
            $table->foreignId('devis_id')
                ->nullable()
                ->constrained('devis')
                ->nullOnDelete();

            // Numéro de facture
            $table->string('numero', 50);

            // Dates
            $table->date('date_emission');
            $table->date('date_echeance')->nullable();

            // Statut de la facture
            $table->string('statut')->default('brouillon');

            // Montants financiers
            $table->decimal('montant_ht', 10, 2)->default(0);
            $table->decimal('taux_tva', 5, 2)->default(20);
            $table->decimal('montant_tva', 10, 2)->default(0);
            $table->decimal('montant_ttc', 10, 2)->default(0);

            // Informations complémentaires
            $table->text('notes')->nullable();

            $table->timestamps();

            // Un numéro de facture unique par entreprise
            $table->unique(
                ['entreprise_id', 'numero'],
                'factures_entreprise_numero_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('factures');
    }
};