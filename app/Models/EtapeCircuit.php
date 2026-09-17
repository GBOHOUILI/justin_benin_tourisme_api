<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EtapeCircuit extends Model
{
    protected $table = 'etape_circuit';

    protected $fillable = [
        'id_circuit',
        'id_site',
        'id_evnmt',
        'ordre',
        'id_reservation',
    ];

    public function circuit()
    {
        return $this->belongsTo(Circuit::class, 'id_circuit');
    }

    public function site()
    {
        return $this->belongsTo(Site::class, 'id_site');
    }

    public function evenement()
    {
        return $this->belongsTo(Evenement::class, 'id_evnmt');
    }

    public function reservation()
    {
        return $this->belongsTo(Reservation::class, 'id_reservation');
    }
}
