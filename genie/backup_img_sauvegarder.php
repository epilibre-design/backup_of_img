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

    include_spip('inc/backup_img');

    $heure        = (int) lire_config('backup_img/cron_heure',        '0');
    $jour_semaine = (int) lire_config('backup_img/cron_jour_semaine', '1');
    $jour_mois    = (int) lire_config('backup_img/cron_jour_mois',    '1');

    $fenetre = backup_img_fenetre_planification($periode, $heure, $jour_semaine, $jour_mois);
    if (!$fenetre || (int) $lastrun >= $fenetre) {
        spip_log(
            'backup_img genie: pas de déclenchement — lastrun=' . $lastrun
            . ', fenetre=' . ($fenetre ?: 0)
            . ', heure_serveur=' . date('Y-m-d H:i:s'),
            'backup_img.' . _LOG_INFO
        );
        return 0;
    }

    $controle = backup_img_peut_creer();
    if (!$controle['peut']) {
        spip_log('backup_img genie: création bloquée — limite ' . $controle['raison'] . ' atteinte, supprimez des sauvegardes existantes', 'backup_img.' . _LOG_ERREUR);
        return 0;
    }

    $chemin = backup_img_creer_archive();

    if (!$chemin) {
        spip_log('backup_img genie: échec de création de l\'archive', 'backup_img.' . _LOG_ERREUR);
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

    spip_log('backup_img genie: sauvegarde automatique réussie', 'backup_img.' . _LOG_INFO_IMPORTANTE);

    return time();
}
