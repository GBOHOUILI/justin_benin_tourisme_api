<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;

class Admin extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $table = 'admin';

    protected $fillable = [
        'nom',
        'prenom',
        'tel',
        'password',
        'status',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'password' => 'hashed',
    ];

    /**
     * Un admin gère plusieurs sites.
     */
    public function sites()
    {
        return $this->hasMany(Site::class, 'id_admin');
    }

    /**
     * Un admin gère plusieurs événements.
     */
    public function evenements()
    {
        return $this->hasMany(Evenement::class, 'id_admin');
    }

    /**
     * Les fonctionnalités accessibles à cet admin (table pivot acceder).
     */
    public function fonctionnalites()
    {
        return $this->belongsToMany(Fonctionnalite::class, 'acceder', 'id_admin', 'id_fonc')
                    ->withPivot('status');
    }
}