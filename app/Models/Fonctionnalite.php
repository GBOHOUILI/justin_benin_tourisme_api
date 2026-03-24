<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Fonctionnalite extends Model
{
    protected $table = 'fonctionnalite';

    protected $fillable = [
        'libelle',
        'type',
    ];

    // Admins ayant accès à cette fonctionnalité
    public function admins()
    {
        return $this->belongsToMany(Admin::class, 'acceder', 'id_fonc', 'id_admin')
                    ->withPivot('status');
    }

    // Users ayant accès à cette fonctionnalité
    public function users()
    {
        return $this->belongsToMany(User::class, 'voir', 'id_fonc', 'id_user')
                    ->withPivot('status');
    }
}