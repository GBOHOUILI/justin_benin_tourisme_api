<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Abonnement extends Model
{
    protected $table = 'abonnement';

    protected $fillable = [
        'id_prestataire',
        'id_plan',
        'date_debut',
        'date_fin',
        'statut',
    ];

    protected $casts = [
        'date_debut' => 'date',
        'date_fin' => 'date',
    ];

    public function prestataire()
    {
        return $this->belongsTo(Prestataire::class, 'id_prestataire');
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class, 'id_plan');
    }

    public function factures()
    {
        return $this->hasMany(FactureAbonnement::class, 'id_abonnement');
    }

    public function estActif(): bool
    {
        // date_fin est castée en date (minuit) - comparer à la fin de journée pour
        // que l'abonnement reste valide jusqu'au bout de son dernier jour.
        return $this->statut === 'actif'
            && $this->date_fin !== null
            && $this->date_fin->copy()->endOfDay()->isFuture();
    }
}
