<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Site extends Model
{
    use HasFactory;

    protected $table = 'site';

    protected $fillable = [
        'libelle',
        'adresse',
        'longitude',
        'latitude',
        'description',
        'points_forts',
        'inclus',
        'non_inclus',
        'infos_pratiques',
        'recommandations',
        'duree_visite',
        'difficulte',
        'ouverture',
        'fermeture',
        'status',
        'id_cat_site',
        'id_admin',
        'id_prestataire',
        'id_region',
        'id_responsable',
    ];

    protected $casts = [
        'ouverture' => 'datetime',
        'fermeture' => 'datetime',
        'points_forts' => 'array',
        'inclus' => 'array',
        'non_inclus' => 'array',
    ];

    public function categorie()
    {
        return $this->belongsTo(CatSite::class, 'id_cat_site');
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