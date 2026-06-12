<?php
if (!defined('_ECRIRE_INC_VERSION')) {
    return;
}

function exec_backup_img_dist(): void
{
    $contexte = array_diff_key($_GET ?? [], ['exec' => 1, 'lang' => 1]);
    echo recuperer_fond('prive/squelettes/contenu/backup_img', $contexte);
}
