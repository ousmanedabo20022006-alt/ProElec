<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Entreprise extends Model
{
    protected $fillable = [
        'nom',
        'email',
        'telephone',
        'adresse',
        'code_postal',
        'ville',
        'siret',
        'numero_tva',
    ];

    /**
     * Les utilisateurs appartenant à cette entreprise.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Les clients appartenant à cette entreprise.
     */
    public function clients(): HasMany
    {
        return $this->hasMany(Client::class);
    }
}