<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Hotel extends Model
{
    use HasFactory;

    protected $table = 'hotel';

    protected $fillable = [
        'libelle',
        'adresse',
        'longitude',
        'latitude',
        'description',
        'nombre_etoiles',
        'status',
        'id_admin',
        'id_prestataire',
        'id_responsable',
        'id_region',
        'points_forts',
        'inclus',
        'non_inclus',
        'infos_pratiques',
        'recommandations',
        'heure_arrivee',
        'heure_depart',
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

    public function chambres()
    {
        return $this->hasMany(Chambre::class, 'id_hotel');
    }

    public function galeries()
    {
        return $this->hasMany(GalerieHotel::class, 'id_hotel');
    }
}
