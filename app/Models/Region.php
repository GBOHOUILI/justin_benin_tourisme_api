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
}
