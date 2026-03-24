<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'nom',
        'prenom',
        'tel',
        'email',
        'password',
        'nationalite',
        'longitude',
        'latitude',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed', // Gère automatiquement le Hash::make() à la création
    ];

    /**
     * Un utilisateur peut effectuer plusieurs réservations.
     */
    public function reservations()
    {
        return $this->hasMany(Reservation::class, 'id_user');
    }

    /**
     * Fonctionnalités visibles par cet utilisateur (table pivot voir).
     */
    public function fonctionnalites()
    {
        return $this->belongsToMany(Fonctionnalite::class, 'voir', 'id_user', 'id_fonc')
                    ->withPivot('status');
    }
}