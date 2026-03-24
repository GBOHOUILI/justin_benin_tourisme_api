<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    protected $table = 'ticket';

    protected $fillable = [
        'numero',
        'id_reservation',
    ];

    public function reservation()
    {
        return $this->belongsTo(Reservation::class, 'id_reservation');
    }

    public function utilisations()
    {
        return $this->hasMany(Utilisation::class, 'id_ticket');
    }
}