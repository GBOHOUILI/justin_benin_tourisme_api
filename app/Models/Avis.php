<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Avis extends Model
{
    use HasFactory;

    protected $table = 'avis';

    protected $fillable = [
        'id_utilisation',
        'message',
        'status',
    ];

    public function utilisation()
    {
        return $this->belongsTo(Utilisation::class, 'id_utilisation');
    }
}