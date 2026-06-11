<?php
if (!defined('_ECRIRE_INC_VERSION')) {
    return;
}

/**
 * Filtre SPIP : formate une taille en octets en chaîne lisible (Ko, Mo, Go).
 */
function backup_img_formater_taille(int $octets): string
{
    if ($octets >= 1073741824) {
        return round($octets / 1073741824, 2) . ' Go';
    }
    if ($octets >= 1048576) {
        return round($octets / 1048576, 2) . ' Mo';
    }
    if ($octets >= 1024) {
        return round($octets / 1024, 1) . ' Ko';
    }
    return $octets . ' o';
}
