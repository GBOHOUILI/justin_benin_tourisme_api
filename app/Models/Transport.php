<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transport extends Model
{
    protected $table = 'transport';

    protected $fillable = [
        'libelle',
        'adresse',
        'longitude',
        'latitude',
        'description',
        'type_transport',
        'capacite',
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

    public function trajets()
    {
        return $this->hasMany(Trajet::class, 'id_transport');
    }

    public function galeries()
    {
        return $this->hasMany(GalerieTransport::class, 'id_transport');
    }
}
