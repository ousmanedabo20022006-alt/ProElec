<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Client extends Model
{
    protected $fillable = [
        'entreprise_id',
        'nom',
        'prenom',
        'email',
        'telephone',
        'adresse',
        'code_postal',
        'ville',
        'notes',
    ];

    /**
     * Entreprise à laquelle appartient le client.
     */
    public function entreprise(): BelongsTo
    {
        return $this->belongsTo(Entreprise::class);
    }
}