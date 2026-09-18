<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FactureAbonnement extends Model
{
    protected $table = 'facture_abonnement';

    protected $fillable = [
        'id_abonnement',
        'montant',
        'date_facturation',
        'statut_paiement',
        'reference_transaction',
        'payload_webhook',
    ];

    protected $casts = [
        'date_facturation' => 'datetime',
        'payload_webhook' => 'array',
    ];

    public function abonnement()
    {
        return $this->belongsTo(Abonnement::class, 'id_abonnement');
    }
}
