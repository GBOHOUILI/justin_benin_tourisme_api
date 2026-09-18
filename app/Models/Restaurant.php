<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Restaurant extends Model
{
    protected $table = 'restaurant';

    protected $fillable = [
        'libelle',
        'adresse',
        'longitude',
        'latitude',
        'description',
        'type_cuisine',
        'gamme_prix',
        'status',
        'id_admin',
        'id_prestataire',
        'id_responsable',
        'id_region',
    ];

    public function admin()
    {
        return $this->belongsTo(Admin::class, 'id_admin');
    }

    public function prestataire()
    {
        return $this->belongsTo(Prestataire::class, 'id_prestataire');
    }

    public function responsable()
    {
        return $this->belongsTo(ResponsableRegional::class, 'id_responsable');
    }

    public function region()
    {
        return $this->belongsTo(Region::class, 'id_region');
    }

    public function plats()
    {
        return $this->hasMany(Plat::class, 'id_restaurant');
    }

    public function galeries()
    {
        return $this->hasMany(GalerieRestaurant::class, 'id_restaurant');
    }
}
