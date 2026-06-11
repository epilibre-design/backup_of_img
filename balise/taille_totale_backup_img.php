<?php
use Spip\Compilateur\Noeud\Champ;

if (!defined('_ECRIRE_INC_VERSION')) {
    return;
}

function balise_TAILLE_TOTALE_BACKUP_IMG_dist(Champ $p): Champ
{
    $p->code = '(include_spip("inc/backup_img") ? backup_img_formater_taille(backup_img_taille_totale()) : "0 o")';
    $p->interdire_scripts = false;
    return $p;
}
