<?php

namespace App\Models;

use App\Enums\TypePrestataire;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Prestataire extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $table = 'prestataire';

    protected $fillable = [
        'nom_entreprise',
        'type_prestataire',
        'email',
        'tel',
        'password',
        'status',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'type_prestataire' => TypePrestataire::class,
        'password' => 'hashed',
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
