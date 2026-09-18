<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GalerieHotel extends Model
{
    protected $table = 'galerie_hotel';

    protected $fillable = ['libelle', 'type', 'url_fichier', 'status', 'id_hotel'];

    public function hotel()
    {
        return $this->belongsTo(Hotel::class, 'id_hotel');
    }
}
