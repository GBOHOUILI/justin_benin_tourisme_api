<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\CatEvenmt;
use App\Models\CatSite;
use App\Models\Evenement;
use App\Models\GalerieHotel;
use App\Models\GalerieRestaurant;
use App\Models\GalerieSite;
use App\Models\GalerieTransport;
use App\Models\GallerieEvnmt;
use App\Models\Hotel;
use App\Models\Plat;
use App\Models\Prix;
use App\Models\Region;
use App\Models\Restaurant;
use App\Models\Site;
use App\Models\Temoignage;
use App\Models\Chambre;
use App\Models\Trajet;
use App\Models\Transport;
use App\Models\Ville;
use Illuminate\Database\Seeder;

/**
 * Jeu de démo (contenu réel du Bénin, photos Wikimedia Commons vérifiées une
 * à une avant usage - cf. ROADMAP.md "Constat environnement 2026-09-25").
 * Remplace le jeu de démo original perdu (aucun volume Docker accessible sur
 * la machine ne le contenait). Idempotent (firstOrCreate sur libelle) :
 * `php artisan db:seed --class=DemoContentSeeder` peut être rejoué sans
 * créer de doublons.
 */
class DemoContentSeeder extends Seeder
{
    public function run(): void
    {
        $admin = Admin::first();

        $catSite = fn (string $libelle) => CatSite::where('libelle', $libelle)->value('id');
        $catEvnmt = fn (string $libelle) => CatEvenmt::where('libelle', $libelle)->value('id');
        $regionId = fn (string $nom) => Region::where('nom', $nom)->value('id');

        // ─── Sites ──────────────────────────────────────────────────────
        $abomey = Site::firstOrCreate(['libelle' => "Palais Royal d'Abomey"], [
            'adresse' => 'Abomey, Zou',
            'longitude' => 1.9833,
            'latitude' => 7.1833,
            'description' => "Douze palais des rois du Dahomey, classés au patrimoine mondial de l'UNESCO, et le musée historique qui les prolonge.",
            'points_forts' => ['Bas-reliefs royaux classés UNESCO', 'Musée historique du Dahomey', 'Guide disponible en français, fon et anglais'],
            'inclus' => ['Entrée musée + palais', 'Visite guidée du site royal'],
            'non_inclus' => ['Transport depuis Cotonou', 'Restauration'],
            'infos_pratiques' => 'Fermé le lundi. Photographies interdites à l\'intérieur du musée.',
            'recommandations' => "Prévoir 2h de visite, chapeau et eau recommandés en saison sèche.",
            'duree_visite' => '2h',
            'difficulte' => 'facile',
            'ouverture' => '09:00',
            'fermeture' => '17:00',
            'status' => 'valide',
            'id_cat_site' => $catSite('Patrimoine historique'),
            'id_region' => $regionId('Zou'),
            'id_admin' => $admin->id,
        ]);

        $ouidah = Site::firstOrCreate(['libelle' => 'Route des Esclaves de Ouidah'], [
            'adresse' => 'Esplanade de la Porte du Non-Retour, Ouidah',
            'longitude' => 2.0900,
            'latitude' => 6.3500,
            'description' => "Mémorial à l'extrémité de la Route des esclaves, sur la plage de Ouidah. Parcours de 4 km depuis la place Chacha.",
            'points_forts' => ['Mémorial historique sur la plage de Ouidah', 'Parcours commémoratif de 4 km', "Chargé d'histoire, une étape essentielle du Bénin"],
            'inclus' => ['Accès libre au mémorial'],
            'non_inclus' => ['Guide (disponible en option sur place)'],
            'infos_pratiques' => 'Ouvert tous les jours, accès libre depuis la place Chacha.',
            'recommandations' => 'Meilleure lumière tôt le matin ou en fin de journée pour les photos.',
            'duree_visite' => '1h30',
            'difficulte' => 'facile',
            'status' => 'valide',
            'id_cat_site' => $catSite('Patrimoine historique'),
            'id_region' => $regionId('Atlantique'),
            'id_admin' => $admin->id,
        ]);

        $ganvie = Site::firstOrCreate(['libelle' => 'Cité Lacustre de Ganvié'], [
            'adresse' => 'Sô-Ava, Atlantique',
            'longitude' => 2.4167,
            'latitude' => 6.4667,
            'description' => "Le plus grand village sur pilotis d'Afrique de l'Ouest : 30 000 habitants, un marché flottant à l'aube et des pirogues comme seules rues.",
            'points_forts' => ["Plus grand village sur pilotis d'Afrique de l'Ouest", 'Marché flottant à l\'aube', 'Mode de vie lacustre unique'],
            'inclus' => ['Pirogue partagée avec guide francophone'],
            'non_inclus' => ['Pirogue privée (en option)', 'Repas'],
            'infos_pratiques' => "Départ en pirogue depuis l'embarcadère de Calavi. Prévoir de l'espèce pour les pourboires.",
            'recommandations' => 'Partir tôt le matin pour voir le marché flottant.',
            'duree_visite' => '2h30',
            'difficulte' => 'facile',
            'status' => 'valide',
            'id_cat_site' => $catSite('Site naturel'),
            'id_region' => $regionId('Atlantique'),
            'id_admin' => $admin->id,
        ]);

        $grandPopo = Site::firstOrCreate(['libelle' => 'Plage de Grand-Popo'], [
            'adresse' => 'Grand-Popo, Mono',
            'longitude' => 1.8167,
            'latitude' => 6.2833,
            'description' => "Plage de sable fin bordée de cocotiers, à l'embouchure du fleuve Mono. Calme, loin de l'agitation de Cotonou.",
            'points_forts' => ['Plage de sable fin bordée de cocotiers', 'Embouchure du fleuve Mono', "Calme, loin de l'agitation de Cotonou"],
            'infos_pratiques' => 'Accès libre. Peu d\'ombre naturelle, prévoir crème solaire.',
            'recommandations' => 'Idéal en fin de journée pour le coucher de soleil.',
            'duree_visite' => 'Demi-journée',
            'status' => 'valide',
            'id_cat_site' => $catSite('Plage'),
            'id_region' => $regionId('Mono'),
            'id_admin' => $admin->id,
        ]);

        $sitePhotos = [
            $abomey->id => ['Royal Palaces of Abomey-133469.jpg'],
            $ouidah->id => ['Porte du non-retour au Benin.jpg'],
            $ganvie->id => [
                'The village of Ganvié on Lake Nokoué.jpg',
                'Pirogue à voile ou pirogue à balancier de type béninois sur le fleuve de Ganvié 05.jpg',
            ],
            $grandPopo->id => ['Plage de Grand-Popo (2).jpg'],
        ];
        foreach ($sitePhotos as $idSite => $files) {
            foreach ($files as $i => $file) {
                GalerieSite::firstOrCreate(['id_site' => $idSite, 'url_fichier' => $this->wikimediaUrl($file)], [
                    'libelle' => "Photo " . ($i + 1),
                    'type' => 'image',
                    'status' => true,
                ]);
            }
        }

        foreach ([$abomey->id => 3000, $ouidah->id => 2000, $ganvie->id => 7500, $grandPopo->id => 0] as $idSite => $montant) {
            if ($montant > 0) {
                Prix::firstOrCreate(['id_site' => $idSite, 'libelle' => 'Entrée'], ['montant' => $montant]);
            }
        }

        // ─── Événements ─────────────────────────────────────────────────
        $vodun = Evenement::firstOrCreate(['libelle' => 'Festival Vodun Days'], [
            'adresse' => 'Plage de Ouidah',
            'longitude' => 2.0833,
            'latitude' => 6.3667,
            'description' => 'Trois jours de cérémonies, de masques Zangbéto et de concerts sur la plage de Ouidah, autour du 10 janvier, fête nationale des religions traditionnelles.',
            'date_debut' => '2027-01-09',
            'date_fin' => '2027-01-11',
            'points_forts' => ['Procession en tenue traditionnelle', 'Concerts sur la plage', 'Immersion culturelle unique au monde'],
            'inclus' => ['Accès aux cérémonies publiques'],
            'non_inclus' => ['Hébergement', 'Restauration'],
            'itineraire' => [
                ['titre' => 'Jour 1 — Arrivée et cérémonies d\'ouverture', 'description' => 'Accueil sur la plage de Ouidah, premières cérémonies vodun.'],
                ['titre' => 'Jour 2 — Procession et concerts', 'description' => 'Grande procession en tenues traditionnelles, concerts en soirée sur la plage.'],
                ['titre' => 'Jour 3 — Clôture', 'description' => 'Cérémonies de clôture et marché artisanal.'],
            ],
            'langue' => 'Français, Fon',
            'difficulte' => 'facile',
            'infos_pratiques' => "Prévoir des vêtements clairs et une bouteille d'eau, forte affluence.",
            'recommandations' => "Réserver l'hébergement à Ouidah plusieurs semaines à l'avance.",
            'status' => 'valide',
            'id_cat_evenmt' => $catEvnmt('Festival culturel'),
            'id_region' => $regionId('Atlantique'),
            'id_admin' => $admin->id,
        ]);

        $foire = Evenement::firstOrCreate(['libelle' => "Foire de l'Artisanat de Dantokpa"], [
            'adresse' => 'Marché Dantokpa, Cotonou',
            'longitude' => 2.4333,
            'latitude' => 6.3667,
            'description' => "Le plus grand marché à ciel ouvert d'Afrique de l'Ouest ouvre ses allées artisanales : textiles, poteries, épices et vannerie de tout le pays.",
            'date_debut' => '2027-03-01',
            'date_fin' => '2027-03-05',
            'points_forts' => ["Plus grand marché à ciel ouvert d'Afrique de l'Ouest", 'Artisanat, textiles et épices', 'Marché flottant sur le lac Nokoué'],
            'infos_pratiques' => 'Marché ouvert tous les jours, forte affluence le week-end.',
            'recommandations' => 'Négociation des prix courante ; gardez vos affaires près de vous.',
            'status' => 'valide',
            'id_cat_evenmt' => $catEvnmt('Foire'),
            'id_region' => $regionId('Littoral'),
            'id_admin' => $admin->id,
        ]);

        $evenementPhotos = [
            $vodun->id => '10 Janvier 2023, Fête de vodoun à Ouidah 33.jpg',
            $foire->id => 'Passerelle du marché dantokpa à Cotonou Bénin.jpg',
        ];
        foreach ($evenementPhotos as $idEvnmt => $file) {
            GallerieEvnmt::firstOrCreate(['id_evnmt' => $idEvnmt, 'url_fichier' => $this->wikimediaUrl($file)], [
                'libelle' => 'Photo 1',
                'type' => 'image',
                'status' => true,
            ]);
        }

        Prix::firstOrCreate(['id_evnmt' => $vodun->id, 'libelle' => 'Standard'], ['montant' => 5000]);
        Prix::firstOrCreate(['id_evnmt' => $vodun->id, 'libelle' => 'VIP tribune'], ['montant' => 25000]);
        Prix::firstOrCreate(['id_evnmt' => $foire->id, 'libelle' => 'Entrée'], ['montant' => 0]);

        // ─── Hôtels ─────────────────────────────────────────────────────
        $lagon = Hotel::firstOrCreate(['libelle' => 'Hôtel du Lagon'], [
            'adresse' => 'Bord de mer, Cotonou',
            'longitude' => 2.4300,
            'latitude' => 6.3600,
            'description' => "Bord de mer, piscine extérieure, à dix minutes de l'aéroport Cardinal Bernardin Gantin.",
            'nombre_etoiles' => 4,
            'points_forts' => ['Bord de mer', 'Piscine extérieure', "À 10 min de l'aéroport"],
            'infos_pratiques' => 'Parking gratuit sur place.',
            'heure_arrivee' => '14:00',
            'heure_depart' => '11:00',
            'status' => 'valide',
            'id_region' => $regionId('Littoral'),
            'id_admin' => $admin->id,
        ]);
        $ecoLodge = Hotel::firstOrCreate(['libelle' => 'Éco-lodge Tata Somba'], [
            'adresse' => 'Natitingou, Atacora',
            'longitude' => 1.3833,
            'latitude' => 10.3167,
            'description' => 'Architecture inspirée des tata somba, à la porte de l\'Atacora et du parc de la Pendjari.',
            'nombre_etoiles' => 3,
            'points_forts' => ['Architecture Tata Somba authentique', 'Porte du parc de la Pendjari'],
            'heure_arrivee' => '13:00',
            'heure_depart' => '12:00',
            'status' => 'valide',
            'id_region' => $regionId('Atacora'),
            'id_admin' => $admin->id,
        ]);

        Chambre::firstOrCreate(['id_hotel' => $lagon->id, 'type_chambre' => 'Chambre standard'], ['prix_nuit' => 45000, 'capacite' => 2, 'disponibilite' => true]);
        Chambre::firstOrCreate(['id_hotel' => $lagon->id, 'type_chambre' => 'Suite vue lagune'], ['prix_nuit' => 85000, 'capacite' => 3, 'disponibilite' => true]);
        Chambre::firstOrCreate(['id_hotel' => $ecoLodge->id, 'type_chambre' => 'Case traditionnelle'], ['prix_nuit' => 22000, 'capacite' => 2, 'disponibilite' => true]);
        Chambre::firstOrCreate(['id_hotel' => $ecoLodge->id, 'type_chambre' => 'Case familiale'], ['prix_nuit' => 38000, 'capacite' => 4, 'disponibilite' => true]);

        GalerieHotel::firstOrCreate(['id_hotel' => $lagon->id, 'url_fichier' => $this->wikimediaUrl('Sun Beach Hotel Cotonou, Bénin.jpg')], ['libelle' => 'Photo 1', 'type' => 'image', 'status' => true]);
        GalerieHotel::firstOrCreate(['id_hotel' => $ecoLodge->id, 'url_fichier' => $this->wikimediaUrl('Benin Tata Somba.JPG')], ['libelle' => 'Photo 1', 'type' => 'image', 'status' => true]);

        // ─── Restaurants ────────────────────────────────────────────────
        $mamanBenin = Restaurant::firstOrCreate(['libelle' => 'Chez Maman Bénin'], [
            'adresse' => 'Quartier Jéricho, Cotonou',
            'longitude' => 2.4200,
            'latitude' => 6.3650,
            'description' => 'Amiwo, wagasi grillé, poisson braisé. Une institution du quartier Jéricho, ouverte midi et soir.',
            'type_cuisine' => 'Cuisine béninoise',
            'gamme_prix' => 'economique',
            'points_forts' => ['Institution du quartier Jéricho', 'Cuisine béninoise traditionnelle'],
            'horaires' => 'Lun-Dim 11h-22h',
            'status' => 'valide',
            'id_region' => $regionId('Littoral'),
            'id_admin' => $admin->id,
        ]);
        $leLagon = Restaurant::firstOrCreate(['libelle' => 'Restaurant Le Lagon'], [
            'adresse' => 'Haie Vive, Cotonou',
            'longitude' => 2.4100,
            'latitude' => 6.3700,
            'description' => 'Cuisine fusion ouest-africaine, carte des vins, terrasse à Haie Vive.',
            'type_cuisine' => 'Fusion ouest-africaine',
            'gamme_prix' => 'eleve',
            'points_forts' => ['Carte des vins', 'Terrasse'],
            'horaires' => 'Mar-Dim 12h-15h, 19h-23h',
            'status' => 'valide',
            'id_region' => $regionId('Littoral'),
            'id_admin' => $admin->id,
        ]);

        Plat::firstOrCreate(['id_restaurant' => $mamanBenin->id, 'nom' => 'Amiwo poulet'], ['prix' => 4500, 'description' => 'Pâte rouge, poulet mijoté']);
        Plat::firstOrCreate(['id_restaurant' => $mamanBenin->id, 'nom' => 'Poisson braisé + atassi'], ['prix' => 6000, 'description' => 'Bar entier, riz aux haricots']);
        Plat::firstOrCreate(['id_restaurant' => $leLagon->id, 'nom' => 'Menu dégustation'], ['prix' => 22000, 'description' => '4 services']);
        Plat::firstOrCreate(['id_restaurant' => $leLagon->id, 'nom' => 'Plat du jour'], ['prix' => 12000, 'description' => 'Midi uniquement']);

        GalerieRestaurant::firstOrCreate(['id_restaurant' => $mamanBenin->id, 'url_fichier' => $this->wikimediaUrl('Arts culinaire du Bénin - Les plats de la cuisine béninoise 12.jpg')], ['libelle' => 'Photo 1', 'type' => 'image', 'status' => true]);
        GalerieRestaurant::firstOrCreate(['id_restaurant' => $leLagon->id, 'url_fichier' => $this->wikimediaUrl('Arts culinaire du Bénin - Les plats de la cuisine béninoise 14.jpg')], ['libelle' => 'Photo 1', 'type' => 'image', 'status' => true]);

        // ─── Transports ─────────────────────────────────────────────────
        $zem = Transport::firstOrCreate(['libelle' => 'Zem Express'], [
            'adresse' => 'Gare de Dantokpa, Cotonou',
            'longitude' => 2.4300,
            'latitude' => 6.3650,
            'description' => 'Moto-taxi agréée pour vos trajets courts dans l\'agglomération de Cotonou.',
            'type_transport' => 'Moto-taxi',
            'capacite' => 1,
            'duree_trajet_estimee' => '30 min',
            'status' => 'valide',
            'id_region' => $regionId('Littoral'),
            'id_admin' => $admin->id,
        ]);
        $baobab = Transport::firstOrCreate(['libelle' => 'Baobab Express'], [
            'adresse' => 'Gare routière, Cotonou',
            'longitude' => 2.4350,
            'latitude' => 6.3600,
            'description' => "Liaison quotidienne vers l'Atacora, 9h de trajet, bagage 20 kg inclus.",
            'type_transport' => 'Bus climatisé',
            'capacite' => 42,
            'duree_trajet_estimee' => '9h',
            'status' => 'valide',
            'id_region' => $regionId('Littoral'),
            'id_admin' => $admin->id,
        ]);

        $cotonou = Ville::where('nom', 'Cotonou')->value('id');
        $portoNovo = Ville::where('nom', 'Porto-Novo')->value('id');
        $natitingou = Ville::where('nom', 'Natitingou')->value('id');

        Trajet::firstOrCreate(['id_transport' => $zem->id, 'id_ville_depart' => $cotonou, 'id_ville_arrivee' => $portoNovo], ['horaire_depart' => '08:00', 'prix' => 3500]);
        Trajet::firstOrCreate(['id_transport' => $baobab->id, 'id_ville_depart' => $cotonou, 'id_ville_arrivee' => $natitingou], ['horaire_depart' => '06:30', 'prix' => 12000]);

        GalerieTransport::firstOrCreate(['id_transport' => $zem->id, 'url_fichier' => $this->wikimediaUrl('Le transport avec moto à Cotonou au Bénin.jpg')], ['libelle' => 'Photo 1', 'type' => 'image', 'status' => true]);
        GalerieTransport::firstOrCreate(['id_transport' => $baobab->id, 'url_fichier' => $this->wikimediaUrl('Le transport avec Taxi à Cotonou au Bénin.jpg')], ['libelle' => 'Photo 1', 'type' => 'image', 'status' => true]);

        // ─── Témoignages plateforme ─────────────────────────────────────
        Temoignage::firstOrCreate(['nom' => 'Marie Dossou'], ['role' => 'Prestataire', 'message' => "Totché m'a permis de doubler mes réservations en trois mois.", 'actif' => true]);
        Temoignage::firstOrCreate(['nom' => 'Jean Koudjo'], ['role' => 'Touriste', 'message' => 'Réserver ma pirogue pour Ganvié a pris deux minutes, tout était clair.', 'actif' => true]);
        Temoignage::firstOrCreate(['nom' => 'Aline Sènan'], ['role' => 'Responsable régional', 'message' => "La validation des fiches se fait en quelques clics, plus besoin d'échanger des dizaines d'emails.", 'actif' => true]);
    }

    private function wikimediaUrl(string $filename): string
    {
        return 'https://commons.wikimedia.org/wiki/Special:FilePath/' . rawurlencode($filename) . '?width=1200';
    }
}
