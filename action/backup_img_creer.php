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

    $controle = backup_img_peut_creer();
    if (!$controle['peut']) {
        spip_log('backup_img: création bloquée — limite ' . $controle['raison'], 'backup_img.' . _LOG_AVERTISSEMENT);
        include_spip('inc/headers');
        redirige_par_entete(generer_url_ecrire('backup_img'));
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
