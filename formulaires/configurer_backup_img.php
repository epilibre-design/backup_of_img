<?php
if (!defined('_ECRIRE_INC_VERSION')) {
    return;
}

function formulaires_configurer_backup_img_charger_dist(): array
{
    return [
        'prefixe'         => lire_config('backup_img/prefixe',         'backup_img'),
        'format_date'     => lire_config('backup_img/format_date',     'Ymd_His'),
        'dossier_local'   => lire_config('backup_img/dossier_local',   'tmp/backup_img/'),
        'max_sauvegardes' => lire_config('backup_img/max_sauvegardes', '10'),
        'max_espace_mo'   => lire_config('backup_img/max_espace_mo',   '500'),
        'cron_periode'    => lire_config('backup_img/cron_periode',    '0'),
        'ftp_actif'       => lire_config('backup_img/ftp_actif',       '0'),
        'ftp_hote'        => lire_config('backup_img/ftp_hote',        ''),
        'ftp_port'        => lire_config('backup_img/ftp_port',        '21'),
        'ftp_login'       => lire_config('backup_img/ftp_login',       ''),
        'ftp_pass'        => lire_config('backup_img/ftp_pass',        ''),
        'ftp_dossier'     => lire_config('backup_img/ftp_dossier',     '/'),
    ];
}

function formulaires_configurer_backup_img_verifier_dist(): array
{
    $erreurs = [];

    $prefixe = _request('prefixe');
    if (empty($prefixe) || !preg_match('/^[a-zA-Z0-9_-]+$/', $prefixe)) {
        $erreurs['prefixe'] = _T('backup_img:erreur_prefixe_invalide');
    }

    $max_sauvegardes = (int) _request('max_sauvegardes');
    if ($max_sauvegardes < 1) {
        $erreurs['max_sauvegardes'] = _T('backup_img:erreur_valeur_positive');
    }

    $max_espace_mo = (int) _request('max_espace_mo');
    if ($max_espace_mo < 1) {
        $erreurs['max_espace_mo'] = _T('backup_img:erreur_valeur_positive');
    }

    return $erreurs;
}

function formulaires_configurer_backup_img_traiter_dist(): array
{
    $retours = [];

    if (_request('_test_ftp')) {
        include_spip('inc/backup_img_ftp');
        $resultat = backup_img_ftp_tester_connexion(
            _request('ftp_hote'),
            (int) _request('ftp_port'),
            _request('ftp_login'),
            _request('ftp_pass'),
            _request('ftp_dossier')
        );
        if ($resultat === true) {
            $retours['message_ok'] = _T('backup_img:info_ftp_connexion_ok');
        } else {
            $retours['message_erreur'] = _T('backup_img:erreur_ftp_connexion', ['erreur' => $resultat]);
        }
        $retours['editable'] = true;
        return $retours;
    }

    include_spip('inc/cvt_configurer');
    $trace = cvtconf_formulaires_configurer_enregistre('configurer_backup_img', []);

    $retours['message_ok'] = _T('config_info_enregistree') . $trace;
    $retours['editable'] = true;

    return $retours;
}
