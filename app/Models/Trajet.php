<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Trajet extends Model
{
    protected $table = 'trajet';

    protected $fillable = ['id_transport', 'id_ville_depart', 'id_ville_arrivee', 'horaire_depart', 'prix'];

    public function transport()
    {
        return $this->belongsTo(Transport::class, 'id_transport');
    }

    public function villeDepart()
    {
        return $this->belongsTo(Ville::class, 'id_ville_depart');
    }

    public function villeArrivee()
    {
        return $this->belongsTo(Ville::class, 'id_ville_arrivee');
    }
}
