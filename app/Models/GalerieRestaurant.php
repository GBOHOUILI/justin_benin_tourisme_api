<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GalerieRestaurant extends Model
{
    protected $table = 'galerie_restaurant';

    protected $fillable = ['libelle', 'type', 'url_fichier', 'status', 'id_restaurant'];

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class, 'id_restaurant');
    }
}
