<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ville extends Model
{
    protected $table = 'ville';

    protected $fillable = ['nom', 'id_region'];

    public function region()
    {
        return $this->belongsTo(Region::class, 'id_region');
    }

    public function trajetsDepart()
    {
        return $this->hasMany(Trajet::class, 'id_ville_depart');
    }

    public function trajetsArrivee()
    {
        return $this->hasMany(Trajet::class, 'id_ville_arrivee');
    }
}
