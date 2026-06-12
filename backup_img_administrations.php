<?php
if (!defined('_ECRIRE_INC_VERSION')) {
    return;
}

function backup_img_upgrade(string $nom_meta_base_version, string $version_cible): void
{
    $maj = [];

    $maj['create'] = [
        ['ecrire_config', 'backup_img/prefixe',          'backup_img'],
        ['ecrire_config', 'backup_img/format_date',       'Ymd_His'],
        ['ecrire_config', 'backup_img/dossier_local',     'tmp/backup_img/'],
        ['ecrire_config', 'backup_img/max_sauvegardes',   '10'],
        ['ecrire_config', 'backup_img/max_espace_mo',     '500'],
        ['ecrire_config', 'backup_img/cron_periode',      '0'],
        ['ecrire_config', 'backup_img/ftp_actif',         '0'],
        ['ecrire_config', 'backup_img/ftp_hote',          ''],
        ['ecrire_config', 'backup_img/ftp_port',          '21'],
        ['ecrire_config', 'backup_img/ftp_login',         ''],
        ['ecrire_config', 'backup_img/ftp_pass',          ''],
        ['ecrire_config', 'backup_img/ftp_dossier',       'backup_img/'],
        ['ecrire_config', 'backup_img/format', 'zip'],
    ];

    $maj['1.1.0'] = [
        ['ecrire_config', 'backup_img/ftp_dossier', 'backup_img/'],
    ];

    $maj['1.2.0'] = [
        ['ecrire_config', 'backup_img/format', 'zip'],
    ];

    include_spip('base/upgrade');
    maj_plugin($nom_meta_base_version, $version_cible, $maj);
}

function backup_img_vider_tables(string $nom_meta_base_version): void
{
    effacer_config('backup_img/prefixe');
    effacer_config('backup_img/format_date');
    effacer_config('backup_img/dossier_local');
    effacer_config('backup_img/max_sauvegardes');
    effacer_config('backup_img/max_espace_mo');
    effacer_config('backup_img/cron_periode');
    effacer_config('backup_img/ftp_actif');
    effacer_config('backup_img/ftp_hote');
    effacer_config('backup_img/ftp_port');
    effacer_config('backup_img/ftp_login');
    effacer_config('backup_img/ftp_pass');
    effacer_config('backup_img/ftp_dossier');
    effacer_config('backup_img/format');
    effacer_meta($nom_meta_base_version);
}
