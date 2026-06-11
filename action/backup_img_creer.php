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
