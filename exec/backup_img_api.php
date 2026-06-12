<?php
if (!defined('_ECRIRE_INC_VERSION')) {
    return;
}

function exec_backup_img_api_dist(): void
{
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
        if (function_exists('fastcgi_finish_request')) {
            header('Content-Type: application/json');
            echo json_encode($etat);
            fastcgi_finish_request();
            backup_img_executer_job($hash);
            exit;
        }
        // Fallback sans fastcgi : exécute de façon synchrone (poll bloquant mais fonctionnel)
        backup_img_executer_job($hash);
        $etat = backup_img_lire_etat($hash);
    }

    header('Content-Type: application/json');
    echo json_encode($etat ?: ['state' => 'unknown', 'hash' => $hash, 'percent' => 0]);
    exit;
}
