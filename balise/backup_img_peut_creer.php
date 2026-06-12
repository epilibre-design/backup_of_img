<?php
use Spip\Compilateur\Noeud\Champ;

if (!defined('_ECRIRE_INC_VERSION')) {
    return;
}

function balise_BACKUP_IMG_PEUT_CREER_dist(Champ $p): Champ
{
    $p->code = '(include_spip("inc/backup_img") ? (backup_img_peut_creer()["peut"] ? "oui" : "non") : "oui")';
    $p->interdire_scripts = false;
    return $p;
}
