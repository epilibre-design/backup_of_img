<?php
if (!defined('_ECRIRE_INC_VERSION')) {
    return;
}

function action_backup_img_telecharger_dist(): void
{
    $securiser_action = charger_fonction('securiser_action', 'inc');
    $nom = basename((string) $securiser_action());

    include_spip('inc/autoriser');
    if (!autoriser('backupimgtelecharger')) {
        http_response_code(403);
        exit;
    }

    include_spip('inc/backup_img');

    if (!backup_img_nom_valide($nom)) {
        http_response_code(400);
        exit;
    }
    $dossier = backup_img_dossier_local();

    if (!$dossier) {
        http_response_code(500);
        exit;
    }

    $chemin = $dossier . $nom;

    if (!is_file($chemin) || !is_readable($chemin)) {
        http_response_code(404);
        exit;
    }

    $taille      = filesize($chemin);
    $content_type = str_ends_with(strtolower($nom), '.tar') ? 'application/x-tar' : 'application/zip';

    header('Content-Type: ' . $content_type);
    header('Content-Disposition: attachment; filename="' . addslashes($nom) . '"');
    header('Content-Length: ' . $taille);
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');

    // Flush output buffer avant readfile pour les gros fichiers
    if (ob_get_level()) {
        ob_end_clean();
    }

    readfile($chemin);
    exit;
}
