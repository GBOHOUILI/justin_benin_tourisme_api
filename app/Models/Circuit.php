<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Circuit extends Model
{
    protected $table = 'circuit';

    protected $fillable = [
        'libelle',
        'description',
        'id_user',
        'genere_par_ia',
    ];

    protected $casts = [
        'genere_par_ia' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    public function etapes()
    {
        return $this->hasMany(EtapeCircuit::class, 'id_circuit')->orderBy('ordre');
    }
}
