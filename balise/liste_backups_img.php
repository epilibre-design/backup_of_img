<?php
use Spip\Compilateur\Noeud\Champ;

if (!defined('_ECRIRE_INC_VERSION')) {
    return;
}

function balise_LISTE_BACKUPS_IMG_dist(Champ $p): Champ
{
    $p->code = '(include_spip("inc/backup_img") ? backup_img_liste_locale() : [])';
    $p->interdire_scripts = false;
    return $p;
}
