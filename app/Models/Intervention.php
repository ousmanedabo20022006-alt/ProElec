<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Intervention extends Model
{
    protected $fillable = [
        'entreprise_id',
        'client_id',
        'titre',
        'description',
        'adresse',
        'code_postal',
        'ville',
        'date_prevue',
        'statut',
    ];

    /**
     * Entreprise propriétaire de l'intervention.
     */
    public function entreprise(): BelongsTo
    {
        return $this->belongsTo(Entreprise::class);
    }

    /**
     * Client concerné par l'intervention.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}