<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Site extends Model
{
    protected $table = 'site';

    protected $fillable = [
        'libelle',
        'adresse',
        'longitude',
        'latitude',
        'description',
        'ouverture',
        'fermeture',
        'status',
        'id_cat_site',
        'id_admin',
    ];

    protected $casts = [
        'ouverture' => 'datetime',
        'fermeture' => 'datetime',
    ];

    public function categorie()
    {
        return $this->belongsTo(CatSite::class, 'id_cat_site');
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class, 'id_admin');
    }

    public function galeries()
    {
        return $this->hasMany(GalerieSite::class, 'id_site');
    }

    public function reservations()
    {
        return $this->hasMany(Reservation::class, 'id_site');
    }

    public function prix()
    {
        return $this->hasMany(Prix::class, 'id_site');
    }

    // Relation many-to-many avec Evenement via la table disposer
    public function evenements()
    {
        return $this->belongsToMany(Evenement::class, 'disposer', 'id_site', 'id_evnmt');
    }
}