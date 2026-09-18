<?php

namespace App\Models;

use App\Enums\TypePrestataire;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Prestataire extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $table = 'prestataire';

    protected $fillable = [
        'nom_entreprise',
        'type_prestataire',
        'email',
        'tel',
        'password',
        'status',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'type_prestataire' => TypePrestataire::class,
        'password' => 'hashed',
    ];

    public function sites()
    {
        return $this->hasMany(Site::class, 'id_prestataire');
    }

    public function evenements()
    {
        return $this->hasMany(Evenement::class, 'id_prestataire');
    }

    public function hotels()
    {
        return $this->hasMany(Hotel::class, 'id_prestataire');
    }

    public function restaurants()
    {
        return $this->hasMany(Restaurant::class, 'id_prestataire');
    }

    public function transports()
    {
        return $this->hasMany(Transport::class, 'id_prestataire');
    }

    public function abonnements()
    {
        return $this->hasMany(Abonnement::class, 'id_prestataire');
    }

    /** Abonnement en cours (actif ou en attente de paiement), le plus récent d'abord. */
    public function abonnementCourant()
    {
        return $this->abonnements()
            ->whereIn('statut', ['actif', 'en_attente'])
            ->latest()
            ->first();
    }

    /**
     * Précondition à la création de fiche (cf. document de référence : "Le prestataire
     * dispose d'un compte professionnel actif et d'un abonnement en cours de validité").
     */
    public function abonnementActif(): bool
    {
        return $this->abonnements()
            ->where('statut', 'actif')
            ->whereDate('date_fin', '>=', now()->toDateString())
            ->exists();
    }
}
