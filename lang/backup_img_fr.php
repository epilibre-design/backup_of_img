<?php
// This is a SPIP language file  --  Ceci est un fichier langue de SPIP
return [
    // B
    'bouton_creer_sauvegarde'           => 'Créer une nouvelle sauvegarde',
    'bouton_supprimer'                   => 'Supprimer',
    'bouton_telecharger'                 => 'Télécharger',
    'bouton_tester_ftp'                  => 'Tester la connexion FTP',

    'confirmer_supprimer'                => 'Supprimer cette sauvegarde ?',
    'lien_configurer'                    => 'Configurer le plugin (format, limites, sauvegarde automatique, FTP…)',

    'avertissement_limite_atteinte'      => 'Limite atteinte. Supprimez des sauvegardes existantes pour pouvoir en créer une nouvelle.',

    // C
    'champ_cron_periode'                 => 'Fréquence des sauvegardes automatiques',
    'champ_dossier_local'                => 'Dossier de stockage local',
    'champ_format_date'                  => 'Format de date dans le nom du fichier',
    'champ_ftp_actif'                    => 'Activer le stockage FTP en miroir',
    'champ_ftp_dossier'                  => 'Dossier distant FTP',
    'champ_ftp_hote'                     => 'Hôte FTP',
    'champ_ftp_login'                    => 'Login FTP',
    'champ_ftp_pass'                     => 'Mot de passe FTP',
    'champ_format'                       => 'Format d\'archive',
    'champ_ftp_port'                     => 'Port FTP',
    'champ_max_espace_mo'                => 'Espace maximum (Mo)',
    'champ_max_sauvegardes'              => 'Nombre maximum de sauvegardes à conserver',
    'champ_prefixe'                      => 'Préfixe du nom de fichier ZIP',

    // E
    'erreur_creation_dossier'            => 'Impossible de créer le dossier de sauvegarde : @dossier@',
    'erreur_creation_zip'                => 'Erreur lors de la création du ZIP',
    'erreur_espace_insuffisant'          => 'Espace insuffisant pour réaliser cette sauvegarde : le dossier IMG/ pèse @taille_img@ Mo mais l\'espace maximum configuré est @max_espace@ Mo.',
    'explication_dossier_local'          => 'Chemin relatif à la racine du site SPIP',
    'explication_format'                 => 'TAR utilise moins de mémoire et est plus rapide pour les gros dossiers. Nécessite le binaire <code>tar</code> sur le serveur. Si indisponible, ZIP est utilisé automatiquement.',
    'erreur_ftp_connexion'               => 'Connexion FTP impossible : @erreur@',
    'erreur_prefixe_invalide'            => 'Le préfixe doit contenir uniquement des lettres, chiffres, tirets ou underscores',
    'erreur_sauvegarde_introuvable'      => 'Sauvegarde introuvable',
    'erreur_valeur_positive'             => 'La valeur doit être un entier positif',

    // I
    'info_espace_occupe'                 => 'Les sauvegardes occupent actuellement environ @taille@.',
    'info_aucune_sauvegarde'             => 'Aucune sauvegarde disponible.',
    'info_cron_desactive'                => 'Désactivé',
    'info_cron_hebdomadaire'             => 'Hebdomadaire',
    'info_cron_journalier'               => 'Journalier',
    'info_cron_mensuel'                  => 'Mensuel',
    'info_date_sauvegarde'               => 'Date',
    'info_espace_utilise'                => 'Espace utilisé : @taille@',
    'info_ftp_connexion_ok'              => 'Connexion FTP réussie.',
    'info_ftp_dossier_cree'              => 'Connexion FTP réussie. Dossier « @dossier@ » créé automatiquement.',
    'info_format_tar'                    => 'TAR (recommandé pour les gros IMG)',
    'info_format_zip'                    => 'ZIP (compatible tous hébergements)',
    'info_nb_sauvegardes'                => '@nb@ sauvegarde(s)',
    'info_nom_fichier'                   => 'Fichier',
    'info_sauvegarde_creee'              => 'Sauvegarde créée avec succès : @fichier@',
    'info_sauvegarde_lancee'             => 'Sauvegarde lancée, traitement en arrière-plan…',
    'info_sauvegarde_terminee'           => 'Sauvegarde terminée.',
    'info_taille'                        => 'Taille',

    // T
    'texte_description'                  => 'Gérez vos sauvegardes complètes du dossier IMG/ et planifiez leur création automatique.',
    'titre_boite_taille'                 => 'Taille des sauvegardes',
    'titre_configurer'                   => 'Configuration de Backup IMG',
    'titre_menu'                         => 'Backup IMG',
    'titre_page'                         => 'Sauvegardes IMG',
    'titre_section_ftp'                  => 'Stockage FTP (miroir)',
    'titre_section_general'              => 'Paramètres généraux',
    'titre_section_rotation'             => 'Rotation des sauvegardes',
];
