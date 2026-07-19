<?php

namespace App\Enums;

enum RoleEnum: string
{
    case Proprietaire = 'proprietaire';
    case Vendeur = 'vendeur';
    case Caissier = 'caissier';
    case Magasinier = 'magasinier';
    case Livreur = 'livreur';
}
