<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Avis extends Model
{
    use HasFactory;

    protected $table = 'avis';

    protected $fillable = [
        'id_reservation',
        'message',
        'note',
        'status',
        'id_user',
        'id_hotel',
        'id_restaurant',
        'id_transport',
    ];

    public function reservation()
    {
        return $this->belongsTo(Reservation::class, 'id_reservation');
    }

    // Auteur direct - uniquement pour un avis Hotel/Restaurant/Transport (pas
    // de Reservation à ces 3-là pour retrouver l'auteur, cf. migration).
    public function user()
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    public function hotel()
    {
        return $this->belongsTo(Hotel::class, 'id_hotel');
    }

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class, 'id_restaurant');
    }

    public function transport()
    {
        return $this->belongsTo(Transport::class, 'id_transport');
    }
}