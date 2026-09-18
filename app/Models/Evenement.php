<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Evenement extends Model
{
    protected $table = 'evenement';

    protected $fillable = [
        'libelle',
        'adresse',
        'longitude',
        'latitude',
        'description',
        'date_debut',
        'date_fin',
        'status',
        'id_cat_evenmt',
        'id_admin',
        'id_prestataire',
        'id_region',
        'id_responsable',
    ];

    protected $casts = [
        'date_debut' => 'datetime',
        'date_fin'   => 'datetime',
    ];

    public function categorie()
    {
        return $this->belongsTo(CatEvenmt::class, 'id_cat_evenmt');
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class, 'id_admin');
    }

    public function prestataire()
    {
        return $this->belongsTo(Prestataire::class, 'id_prestataire');
    }

    public function region()
    {
        return $this->belongsTo(Region::class, 'id_region');
    }

    public function responsable()
    {
        return $this->belongsTo(ResponsableRegional::class, 'id_responsable');
    }

    public function galeries()
    {
        return $this->hasMany(GallerieEvnmt::class, 'id_evnmt');
    }

    public function reservations()
    {
        return $this->hasMany(Reservation::class, 'id_evnmt');
    }

    public function prix()
    {
        return $this->hasMany(Prix::class, 'id_evnmt');
    }

    // Relation many-to-many avec Site via la table disposer
    public function sites()
    {
        return $this->belongsToMany(Site::class, 'disposer', 'id_evnmt', 'id_site');
    }
}