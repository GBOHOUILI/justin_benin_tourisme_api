<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class ResponsableRegional extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $table = 'responsable_regional';

    protected $fillable = [
        'nom',
        'prenom',
        'tel',
        'password',
        'status',
        'id_region',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'password' => 'hashed',
    ];

    public function region()
    {
        return $this->belongsTo(Region::class, 'id_region');
    }

    public function sites()
    {
        return $this->hasMany(Site::class, 'id_responsable');
    }

    public function evenements()
    {
        return $this->hasMany(Evenement::class, 'id_responsable');
    }

    public function hotels()
    {
        return $this->hasMany(Hotel::class, 'id_responsable');
    }

    public function restaurants()
    {
        return $this->hasMany(Restaurant::class, 'id_responsable');
    }

    public function transports()
    {
        return $this->hasMany(Transport::class, 'id_responsable');
    }

    /** Un responsable sans région assignée valide partout (poste vacant ailleurs). */
    public function estGlobal(): bool
    {
        return $this->id_region === null;
    }
}
