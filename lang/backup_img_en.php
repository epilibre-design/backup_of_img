<?php
// This is a SPIP language file  --  Ceci est un fichier langue de SPIP
return [
    // B
    'bouton_creer_sauvegarde'           => 'Create a new backup',
    'bouton_supprimer'                   => 'Delete',
    'bouton_telecharger'                 => 'Download',
    'bouton_tester_ftp'                  => 'Test FTP connection',

    'avertissement_limite_atteinte'      => 'Limit reached. Delete existing backups to be able to create a new one.',
    'confirmer_supprimer'                => 'Delete this backup?',
    'lien_configurer'                    => 'Configure the plugin (format, limits, automatic backup, FTP…)',

    // C
    'champ_cron_periode'                 => 'Automatic backup frequency',
    'champ_dossier_local'                => 'Local storage folder',
    'champ_format_date'                  => 'Date format in filename',
    'champ_ftp_actif'                    => 'Enable FTP mirror storage',
    'champ_ftp_dossier'                  => 'FTP remote folder',
    'champ_ftp_hote'                     => 'FTP host',
    'champ_ftp_login'                    => 'FTP login',
    'champ_ftp_pass'                     => 'FTP password',
    'champ_format'                       => 'Archive format',
    'champ_ftp_port'                     => 'FTP port',
    'champ_max_espace_mo'                => 'Maximum storage space (MB)',
    'champ_max_sauvegardes'              => 'Maximum number of backups to keep',
    'champ_prefixe'                      => 'ZIP filename prefix',

    // E
    'erreur_creation_dossier'            => 'Cannot create backup folder: @dossier@',
    'erreur_creation_zip'                => 'Error while creating ZIP',
    'erreur_espace_insuffisant'          => 'Not enough space for this backup: the IMG/ folder is @taille_img@ MB but the configured maximum is @max_espace@ MB.',
    'explication_dossier_local'          => 'Path relative to the SPIP site root',
    'explication_format'                 => 'TAR uses less memory and is faster for large folders. Requires the <code>tar</code> binary on the server. Falls back to ZIP automatically if unavailable.',
    'erreur_ftp_connexion'               => 'FTP connection failed: @erreur@',
    'erreur_prefixe_invalide'            => 'Prefix must contain only letters, digits, hyphens or underscores',
    'erreur_sauvegarde_introuvable'      => 'Backup not found',
    'erreur_valeur_positive'             => 'Value must be a positive integer',

    // I
    'info_espace_occupe'                 => 'Backups currently take up approximately @taille@.',
    'info_aucune_sauvegarde'             => 'No backup available.',
    'info_cron_desactive'                => 'Disabled',
    'info_cron_hebdomadaire'             => 'Weekly',
    'info_cron_journalier'               => 'Daily',
    'info_cron_mensuel'                  => 'Monthly',
    'info_date_sauvegarde'               => 'Date',
    'info_espace_utilise'                => 'Used space: @taille@',
    'info_ftp_connexion_ok'              => 'FTP connection successful.',
    'info_ftp_dossier_cree'              => 'FTP connection successful. Folder "@dossier@" created automatically.',
    'info_format_tar'                    => 'TAR (recommended for large IMG)',
    'info_format_zip'                    => 'ZIP (compatible with all hosts)',
    'info_nb_sauvegardes'                => '@nb@ backup(s)',
    'info_nom_fichier'                   => 'File',
    'info_sauvegarde_creee'              => 'Backup created successfully: @fichier@',
    'info_sauvegarde_lancee'             => 'Backup started, processing in background…',
    'info_sauvegarde_terminee'           => 'Backup complete.',
    'info_taille'                        => 'Size',

    // T
    'texte_description'                  => 'Manage full backups of your IMG/ folder and schedule automatic creation.',
    'titre_boite_taille'                 => 'Backup size',
    'titre_configurer'                   => 'Backup IMG Configuration',
    'titre_menu'                         => 'Backup IMG',
    'titre_page'                         => 'IMG Backups',
    'titre_section_ftp'                  => 'FTP Storage (mirror)',
    'titre_section_general'              => 'General settings',
    'titre_section_rotation'             => 'Backup rotation',
];
