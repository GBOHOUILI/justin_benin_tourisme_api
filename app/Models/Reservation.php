<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    protected $table = 'reservation';

    protected $fillable = [
        'type', 'prix', 'nombre', 'total',
        'description', 'id_site', 'id_evnmt', 'id_user',
        'id_prix', 'id_commande', 'statut',
    ];

    // ─── CORRECTION CRITIQUE : sans = ['user'] empêche la récursion
    // User → reservations → user → reservations → INFINI
    protected $without = ['user'];

    public function site()
    {
        return $this->belongsTo(Site::class, 'id_site');
    }

    public function evenement()
    {
        return $this->belongsTo(Evenement::class, 'id_evnmt');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class, 'id_reservation');
    }

    public function tarif()
    {
        return $this->belongsTo(Prix::class, 'id_prix');
    }

    public function commande()
    {
        return $this->belongsTo(Commande::class, 'id_commande');
    }
}