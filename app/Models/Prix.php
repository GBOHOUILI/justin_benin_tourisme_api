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
    ];

    public function site()
    {
        return $this->belongsTo(Site::class, 'id_site');
    }

    public function evenement()
    {
        return $this->belongsTo(Evenement::class, 'id_evnmt');
    }
}