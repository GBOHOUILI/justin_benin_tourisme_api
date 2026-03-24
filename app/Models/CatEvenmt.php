<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CatEvenmt extends Model
{
    protected $table = 'cat_evenmt';

    protected $fillable = ['libelle'];

    public function evenements()
    {
        return $this->hasMany(Evenement::class, 'id_cat_evenmt');
    }
}