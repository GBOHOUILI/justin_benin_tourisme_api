<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Favori extends Model
{
    use HasFactory;

    protected $table = 'favori';

    protected $fillable = [
        'id_user',
        'id_site',
        'id_evnmt',
        'id_hotel',
        'id_restaurant',
        'id_transport',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    public function site()
    {
        return $this->belongsTo(Site::class, 'id_site');
    }

    public function evenement()
    {
        return $this->belongsTo(Evenement::class, 'id_evnmt');
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

    // Type logique déduit de la colonne FK renseignée - pratique côté API
    // pour renvoyer {type, item} plutôt que 5 colonnes majoritairement nulles.
    public function getTypeAttribute(): string
    {
        return match (true) {
            $this->id_site !== null => 'site',
            $this->id_evnmt !== null => 'evenement',
            $this->id_hotel !== null => 'hotel',
            $this->id_restaurant !== null => 'restaurant',
            default => 'transport',
        };
    }
}
