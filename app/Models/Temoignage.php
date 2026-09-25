<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Temoignage extends Model
{
    use HasFactory;

    protected $table = 'temoignage';

    protected $fillable = [
        'nom',
        'role',
        'message',
        'photo',
        'actif',
    ];

    protected $casts = [
        'actif' => 'boolean',
    ];
}
