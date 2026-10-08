<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Devis extends Model
{
    protected $fillable = [
        'entreprise_id',
        'client_id',
        'intervention_id',
        'numero',
        'date_emission',
        'date_validite',
        'statut',
        'montant_ht',
        'taux_tva',
        'montant_tva',
        'montant_ttc',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'date_emission' => 'date',
            'date_validite' => 'date',
            'montant_ht' => 'decimal:2',
            'taux_tva' => 'decimal:2',
            'montant_tva' => 'decimal:2',
            'montant_ttc' => 'decimal:2',
        ];
    }

    public function entreprise(): BelongsTo
    {
        return $this->belongsTo(Entreprise::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function intervention(): BelongsTo
    {
        return $this->belongsTo(Intervention::class);
    }
}