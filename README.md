# Backup IMG — Plugin SPIP

Sauvegarde complète du dossier `IMG/` de votre site SPIP, avec stockage local et miroir FTP optionnel.

## Fonctionnalités

- **Sauvegarde manuelle** depuis l'espace privé, en un clic
- **Sauvegarde automatique** planifiée (journalière, hebdomadaire, mensuelle) via le mécanisme `genie` de SPIP
- **Deux formats d'archive** : ZIP (universel) ou TAR (plus rapide, moins gourmand en mémoire pour les gros dossiers)
- **Progression en temps réel** : barre de progression et pourcentage mis à jour pendant la sauvegarde
- **Miroir FTP** : chaque archive est copiée automatiquement sur un serveur FTP distant
- **Limites configurables** : nombre maximum de sauvegardes et/ou espace disque maximum — quand la limite est atteinte, la création est bloquée et un avertissement s'affiche
- **Téléchargement** des archives directement depuis l'interface
- **Suppression** avec confirmation
- **Accès réservé aux webmestres**
- Interface bilingue **français / anglais**

## Compatibilité

- SPIP 4.4.x et supérieur
- PHP 8.1+
- Extension PHP `ZipArchive` (pour le format ZIP)
- Binaire `tar` sur le serveur (pour le format TAR, avec fallback automatique sur ZIP si absent)

## Installation

Déposez le dossier du plugin dans `plugins/` de votre site SPIP, puis activez-le depuis *Configuration → Gestion des plugins*.

## Configuration

Depuis la page principale (*Administration → Backup IMG*), cliquez sur le lien **Configurer le plugin** en bas de page. Vous accédez ainsi au formulaire de configuration (`?exec=configurer&configurer=backup_img`).

### Paramètres généraux

| Paramètre | Défaut | Description |
|---|---|---|
| Préfixe du nom de fichier | `backup_img` | Début du nom de chaque archive |
| Format de date | `Ymd_His` | Format PHP `date()` inclus dans le nom de fichier |
| Dossier de stockage local | `tmp/backup_img/` | Chemin relatif à la racine du site |
| Format d'archive | `zip` | `zip` ou `tar` |

### Sauvegarde automatique

| Paramètre | Défaut | Description |
|---|---|---|
| Fréquence | Désactivé | Journalier, hebdomadaire ou mensuel |

La sauvegarde automatique est déclenchée par le mécanisme `genie` de SPIP lors du passage du cron (`?var_cron=1`).

### Limites de stockage

| Paramètre | Défaut | Description |
|---|---|---|
| Nombre maximum de sauvegardes | `10` | `0` = illimité |
| Espace maximum (Mo) | `500` | `0` = illimité |

Quand une limite est atteinte, la création de nouvelles sauvegardes est bloquée. Il faut supprimer des archives existantes pour débloquer le bouton.

### Miroir FTP (optionnel)

Activez le stockage FTP pour copier chaque archive sur un serveur distant au moment de sa création. Les archives supprimées depuis l'interface sont également supprimées sur le FTP.

| Paramètre | Description |
|---|---|
| Hôte FTP | Adresse du serveur FTP |
| Port | Par défaut `21` |
| Login / Mot de passe | Identifiants FTP |
| Dossier distant | Dossier de destination sur le serveur FTP |

Un bouton **Tester la connexion FTP** permet de vérifier les paramètres sans effectuer de sauvegarde.

## Utilisation

### Créer une sauvegarde manuellement

Depuis *Administration → Backup IMG*, cliquez sur **Créer une nouvelle sauvegarde**. La barre de progression s'affiche pendant le traitement. Un message de confirmation apparaît à la fin, et la liste des sauvegardes est mise à jour automatiquement.

### Télécharger ou supprimer une sauvegarde

Le tableau liste toutes les archives disponibles avec leur nom, taille et date. Chaque ligne propose un bouton **Télécharger** et un bouton **Supprimer** (avec confirmation).

### Sauvegarde automatique

Choisissez une fréquence dans la section *Sauvegarde automatique* du formulaire de configuration. SPIP déclenchera la sauvegarde lors du passage du cron (`?var_cron=1`), selon la période choisie.

## Structure des fichiers

```
backup_img/
├── paquet.xml
├── backup_img_autoriser.php       — déclaration des autorisations
├── backup_img_administrations.php — defaults de configuration
├── backup_img_pipelines.php       — pipelines SPIP
├── backup_img_fonctions.php       — fonctions utilitaires SPIP (balises)
│
├── action/
│   ├── backup_img_creer.php       — déclenche une sauvegarde (async)
│   ├── backup_img_supprimer.php   — supprime une archive locale (+ FTP)
│   └── backup_img_telecharger.php — sert l'archive au navigateur
│
├── balise/
│   ├── backup_img_peut_creer.php  — balise #BACKUP_IMG_PEUT_CREER
│   ├── liste_backups_img.php      — balise #LISTE_BACKUPS_IMG
│   └── taille_totale_backup_img.php — balise #TAILLE_TOTALE_BACKUP_IMG
│
├── exec/
│   └── backup_img.php             — page principale (backup_img_api)
│
├── formulaires/
│   ├── configurer_backup_img.html — formulaire de configuration
│   └── configurer_backup_img.php  — traitement + test FTP
│
├── genie/
│   └── backup_img_sauvegarder.php — tâche planifiée SPIP
│
├── inc/
│   ├── backup_img.php             — fonctions core (création, liste, limites)
│   └── backup_img_ftp.php         — connexion, upload, suppression FTP
│
├── prive/squelettes/
│   ├── contenu/
│   │   └── backup_img.html        — interface principale
│   └── inclure/
│       ├── backup_img_taille.html — bloc ajax : espace occupé + avertissement
│       └── liste_backups.html     — tableau des sauvegardes
│
├── prive/js/
│   └── backup_img.js              — progression temps réel, états UI
│
└── lang/
    ├── backup_img_fr.php
    └── backup_img_en.php
```

## Détails techniques

### Progression asynchrone

La création d'une archive est lancée en arrière-plan via `register_shutdown_function`. Un fichier JSON d'état (`tmp/backup_img/<hash>.json`) est mis à jour en continu. Le navigateur interroge l'API (`exec=backup_img_api`) toutes les 3 secondes pour mettre à jour la barre de progression.

### Format TAR

Le format TAR utilise `proc_open(tar)`, ce qui maintient une consommation mémoire constante quelle que soit la taille de `IMG/`. La progression est calculée d'après la taille de l'archive en cours de création. Si le binaire `tar` est absent du serveur, le plugin bascule automatiquement sur ZIP.

### Limites de stockage

Les limites portent sur l'espace total occupé par les archives (dossier de stockage local), et non sur la taille du dossier `IMG/` source. Quand une limite est atteinte, la création est bloquée côté serveur (action PHP) et côté interface (bouton désactivé, avertissement affiché). Les sauvegardes existantes ne sont jamais supprimées automatiquement.

## Licence

GNU/GPL — voir `paquet.xml`
