<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Paiement extends Model
{
    protected $table = 'paiement';

    protected $fillable = [
        'id_commande',
        'reference_transaction',
        'montant',
        'moyen',
        'statut',
        'numero_echeance',
        'payload_webhook',
        'paid_at',
    ];

    protected $casts = [
        'payload_webhook' => 'array',
        'paid_at' => 'datetime',
    ];

    public function commande()
    {
        return $this->belongsTo(Commande::class, 'id_commande');
    }
}
