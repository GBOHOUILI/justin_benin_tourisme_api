<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Prix extends Model
{
    protected $table = 'prix';

    protected $fillable = [
        'libelle',
        'montant',
        'id_site',
        'id_evnmt',
        'echelonnable',
        'nombre_echeances',
    ];

    protected $casts = [
        'echelonnable' => 'boolean',
    ];

    public function site()
    {
        return $this->belongsTo(Site::class, 'id_site');
    }

    public function evenement()
    {
        return $this->belongsTo(Evenement::class, 'id_evnmt');
    }

    public function reservations()
    {
        return $this->hasMany(Reservation::class, 'id_prix');
    }
}