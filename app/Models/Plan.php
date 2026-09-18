<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $table = 'plan';

    protected $fillable = [
        'nom',
        'prix_mensuel',
        'nombre_fiches_max',
        'fonctionnalites',
    ];

    protected $casts = [
        'fonctionnalites' => 'array',
    ];

    public function abonnements()
    {
        return $this->hasMany(Abonnement::class, 'id_plan');
    }
}
