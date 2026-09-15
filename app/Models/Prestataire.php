<?php

namespace App\Models;

use App\Enums\TypePrestataire;
use Illuminate\Database\Eloquent\Model;

class Prestataire extends Model
{
    protected $table = 'prestataire';

    protected $fillable = [
        'nom_entreprise',
        'type_prestataire',
        'status',
    ];

    protected $casts = [
        'type_prestataire' => TypePrestataire::class,
    ];

    public function sites()
    {
        return $this->hasMany(Site::class, 'id_prestataire');
    }

    public function evenements()
    {
        return $this->hasMany(Evenement::class, 'id_prestataire');
    }
}
