<?php
if (!defined('_ECRIRE_INC_VERSION')) {
    return;
}

function exec_backup_img_api_dist(): void
{
    // Vider les tampons SPIP : évite que le HTML de la page privée ne soit
    // envoyé avec notre JSON, ce qui rendrait la réponse non parseable.
    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    include_spip('inc/autoriser');
    if (!autoriser('webmestre')) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'forbidden']);
        exit;
    }

    $hash = preg_replace('/[^a-f0-9]/', '', (string) _request('hash'));
    if (!$hash) {
        http_response_code(400);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'missing hash']);
        exit;
    }

    include_spip('inc/backup_img');
    $etat = backup_img_lire_etat($hash);

    if ($etat && $etat['state'] === 'pending') {
        $json = json_encode($etat);

        // Content-Length + Connection: close permettent au navigateur de
        // considérer la réponse comme complète et de fermer la connexion,
        // même si le processus PHP continue à tourner côté serveur.
        header('Content-Type: application/json');
        header('Connection: close');
        header('Content-Length: ' . strlen($json));
        echo $json;
        flush();

        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }

        // Libère le verrou de session : les requêtes de polling qui arrivent
        // sur d'autres workers PHP peuvent s'exécuter sans attendre la fin du backup.
        session_write_close();
        ignore_user_abort(true);
        set_time_limit(0);
        backup_img_executer_job($hash);
        exit;
    }

    header('Content-Type: application/json');
    echo json_encode($etat ?: ['state' => 'unknown', 'hash' => $hash, 'percent' => 0]);
    exit;
}
