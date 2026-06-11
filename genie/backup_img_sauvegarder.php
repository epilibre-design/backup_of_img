<?php
if (!defined('_ECRIRE_INC_VERSION')) {
    return;
}

/**
 * Tâche planifiée SPIP : crée une sauvegarde automatique si la période configurée est écoulée.
 *
 * Déclarée dans paquet.xml avec periode="3600" (vérifie toutes les heures),
 * mais la période réelle est lue depuis la configuration.
 *
 * @param int $lastrun Timestamp de la dernière exécution réussie (0 si jamais exécutée)
 * @return int Timestamp actuel si sauvegarde effectuée, 0 sinon
 */
function genie_backup_img_sauvegarder_dist(int $lastrun): int
{
    $periode = (int) lire_config('backup_img/cron_periode', '0');

    if (!$periode) {
        return 0;
    }

    if ($lastrun && (time() - $lastrun) < $periode) {
        return 0;
    }

    include_spip('inc/backup_img');
    $chemin = backup_img_creer_zip();

    if (!$chemin) {
        spip_log('backup_img genie: échec de création du ZIP', 'backup_img.' . _LOG_ERREUR);
        return 0;
    }

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

    spip_log('backup_img genie: sauvegarde automatique réussie', 'backup_img.' . _LOG_INFO_IMPORTANTE);

    return time();
}
