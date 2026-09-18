<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Chambre extends Model
{
    protected $table = 'chambre';

    protected $fillable = [
        'id_hotel',
        'type_chambre',
        'prix_nuit',
        'capacite',
        'disponibilite',
    ];

    protected $casts = [
        'disponibilite' => 'boolean',
    ];

    public function hotel()
    {
        return $this->belongsTo(Hotel::class, 'id_hotel');
    }
}
