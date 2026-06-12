<?php
if (!defined('_ECRIRE_INC_VERSION')) {
    return;
}

function action_backup_img_supprimer_dist(): void
{
    $securiser_action = charger_fonction('securiser_action', 'inc');
    $nom = basename((string) $securiser_action());

    include_spip('inc/autoriser');
    if (!autoriser('backupimgsupprimer')) {
        spip_log('backup_img: suppression refusée (non webmestre)', 'backup_img.' . _LOG_AVERTISSEMENT);
        include_spip('inc/headers');
        redirige_par_entete(html_entity_decode(generer_url_ecrire('backup_img')));
        return;
    }

    if (!$nom || !preg_match('/\.zip$/i', $nom)) {
        include_spip('inc/headers');
        redirige_par_entete(html_entity_decode(generer_url_ecrire('backup_img')));
        return;
    }

    include_spip('inc/backup_img');
    $dossier = backup_img_dossier_local();

    if ($dossier) {
        $chemin_local = $dossier . $nom;

        $ftp_actif = (int) lire_config('backup_img/ftp_actif', '0');
        $conn_ftp  = null;

        if ($ftp_actif) {
            include_spip('inc/backup_img_ftp');
            $conn_ftp = backup_img_ftp_connecter();
        }

        backup_img_supprimer_fichier($chemin_local, $nom, $conn_ftp);

        if ($conn_ftp) {
            ftp_close($conn_ftp);
        }
    }

    include_spip('inc/headers');
    redirige_par_entete(html_entity_decode(generer_url_ecrire('backup_img')));
}
