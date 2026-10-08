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

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function clients(): HasMany
    {
        return $this->hasMany(Client::class);
    }

    public function interventions(): HasMany
    {
        return $this->hasMany(Intervention::class);
    }

    public function devis(): HasMany
    {
        return $this->hasMany(Devis::class);
    }
}