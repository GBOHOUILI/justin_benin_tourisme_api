<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Restaurant extends Model
{
    use HasFactory;
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
        'commentaire_responsable',
        'id_admin',
        'id_prestataire',
        'id_responsable',
        'id_region',
        'points_forts',
        'inclus',
        'non_inclus',
        'infos_pratiques',
        'recommandations',
        'horaires',
    ];

    protected $casts = [
        'points_forts' => 'array',
        'inclus' => 'array',
        'non_inclus' => 'array',
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

    public function avis()
    {
        return $this->hasMany(Avis::class, 'id_restaurant');
    }
}
