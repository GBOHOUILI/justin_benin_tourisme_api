<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Commande extends Model
{
    protected $table = 'commande';

    protected $fillable = [
        'reference',
        'id_user',
        'montant_total',
        'statut',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    public function reservations()
    {
        return $this->hasMany(Reservation::class, 'id_commande');
    }

    public function paiements()
    {
        return $this->hasMany(Paiement::class, 'id_commande');
    }
}
