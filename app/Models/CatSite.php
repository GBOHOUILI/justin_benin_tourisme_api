<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CatSite extends Model
{
    use HasFactory;

    protected $table = 'cat_site';

    protected $fillable = ['libelle'];

    public function sites()
    {
        return $this->hasMany(Site::class, 'id_cat_site');
    }
}