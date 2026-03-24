<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Utilisation extends Model
{
    protected $table = 'utilisation';

    protected $fillable = [
        'date_visite',
        'heure',
        'id_ticket',
    ];

    protected $casts = [
        'date_visite' => 'date',
        'heure'       => 'datetime',
    ];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class, 'id_ticket');
    }

    public function avis()
    {
        return $this->hasOne(Avis::class, 'id_utilisation');
    }
}