<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Region extends Model
{
    protected $table = 'region';

    protected $fillable = ['nom'];

    public function sites()
    {
        return $this->hasMany(Site::class, 'id_region');
    }

    public function evenements()
    {
        return $this->hasMany(Evenement::class, 'id_region');
    }

    public function responsables()
    {
        return $this->hasMany(ResponsableRegional::class, 'id_region');
    }

    public function hotels()
    {
        return $this->hasMany(Hotel::class, 'id_region');
    }

    public function restaurants()
    {
        return $this->hasMany(Restaurant::class, 'id_region');
    }

    public function transports()
    {
        return $this->hasMany(Transport::class, 'id_region');
    }

    public function villes()
    {
        return $this->hasMany(Ville::class, 'id_region');
    }
}
