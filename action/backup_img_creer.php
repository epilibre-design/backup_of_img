<?php
if (!defined('_ECRIRE_INC_VERSION')) {
    return;
}

function action_backup_img_creer_dist(): void
{
    $securiser_action = charger_fonction('securiser_action', 'inc');
    $securiser_action();

    include_spip('inc/autoriser');
    if (!autoriser('backupimgcreer')) {
        spip_log('backup_img: création refusée (non webmestre)', 'backup_img.' . _LOG_AVERTISSEMENT);
        include_spip('inc/headers');
        redirige_par_entete(generer_url_ecrire('backup_img', 'erreur=acces_refuse'));
        return;
    }

    include_spip('inc/backup_img');

    $max_mo     = (int) lire_config('backup_img/max_espace_mo', '500');
    $max_octets = $max_mo * 1024 * 1024;
    $taille_img = backup_img_taille_dossier(rtrim(_DIR_IMG, '/'));

    if ($taille_img > $max_octets) {
        $taille_img_mo = (int) round($taille_img / (1024 * 1024));
        spip_log(
            "backup_img: espace insuffisant — IMG={$taille_img_mo} Mo, limite={$max_mo} Mo",
            'backup_img.' . _LOG_AVERTISSEMENT
        );
        include_spip('inc/headers');
        redirige_par_entete(str_replace('&amp;', '&', generer_url_ecrire('backup_img',
            'erreur=espace_insuffisant&taille_img=' . $taille_img_mo . '&max_espace=' . $max_mo
        )));
        return;
    }

    $hash = substr(hash('sha256', uniqid('backup_img', true)), 0, 16);
    backup_img_ecrire_etat($hash, [
        'hash'       => $hash,
        'state'      => 'pending',
        'percent'    => 0,
        'processed'  => 0,
        'total'      => 0,
        'nom'        => null,
        'started_at' => null,
        'ended_at'   => null,
        'error'      => null,
    ]);

    include_spip('inc/headers');
    redirige_par_entete(str_replace('&amp;', '&', generer_url_ecrire('backup_img', 'job=' . $hash)));
}
