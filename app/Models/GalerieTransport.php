<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GalerieTransport extends Model
{
    protected $table = 'galerie_transport';

    protected $fillable = ['libelle', 'type', 'url_fichier', 'status', 'id_transport'];

    public function transport()
    {
        return $this->belongsTo(Transport::class, 'id_transport');
    }
}
