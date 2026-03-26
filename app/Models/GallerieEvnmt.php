<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GallerieEvnmt extends Model
{
    protected $table = "gallerie_evnmt";

    protected $fillable = [
        "libelle", // titre/description du média
        "url_fichier", // chemin du fichier sur le disque public
        "type",
        "status",
        "id_evnmt",
    ];

    public function evenement()
    {
        return $this->belongsTo(Evenement::class, "id_evnmt");
    }
}
