<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plat extends Model
{
    protected $table = 'plat';

    protected $fillable = ['id_restaurant', 'nom', 'prix', 'description'];

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class, 'id_restaurant');
    }
}
