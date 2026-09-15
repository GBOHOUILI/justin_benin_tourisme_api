<?php

namespace App\Enums;

enum TypePrestataire: string
{
    case Site = 'site';
    case Evenement = 'evenement';
    case Hotel = 'hotel';
    case Restaurant = 'restaurant';
    case Transport = 'transport';
}
