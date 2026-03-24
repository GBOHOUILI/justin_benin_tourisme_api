<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GalerieSite extends Model
{
    protected $table = 'galerie_site';

    protected $fillable = [
        'libelle',
        'type',
        'status',
        'id_site',
    ];

    public function site()
    {
        return $this->belongsTo(Site::class, 'id_site');
    }
}