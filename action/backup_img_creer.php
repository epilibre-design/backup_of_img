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
        redirige_par_entete(generer_url_ecrire('backup_img',
            'erreur=espace_insuffisant&taille_img=' . $taille_img_mo . '&max_espace=' . $max_mo
        ));
        return;
    }

    $chemin = backup_img_creer_zip();

    if (!$chemin) {
        spip_log('backup_img: échec de création du ZIP', 'backup_img.' . _LOG_ERREUR);
        include_spip('inc/headers');
        redirige_par_entete(generer_url_ecrire('backup_img', 'erreur=creation_zip'));
        return;
    }

    $nom = basename($chemin);

    $ftp_actif = (int) lire_config('backup_img/ftp_actif', '0');
    if ($ftp_actif) {
        include_spip('inc/backup_img_ftp');
        $conn = backup_img_ftp_connecter();
        if ($conn) {
            backup_img_ftp_uploader($chemin, $conn);
            ftp_close($conn);
        }
    }

    backup_img_rotation();

    include_spip('inc/headers');
    redirige_par_entete(generer_url_ecrire('backup_img', 'ok=1&nom=' . rawurlencode($nom)));
}
