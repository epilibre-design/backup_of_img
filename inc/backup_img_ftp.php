<?php
if (!defined('_ECRIRE_INC_VERSION')) {
    return;
}

/**
 * Ouvre une connexion FTP à partir de la configuration.
 * Retourne la ressource FTP ou false.
 */
function backup_img_ftp_connecter(): mixed
{
    return backup_img_ftp_connecter_avec(
        (string) lire_config('backup_img/ftp_hote',   ''),
        (int)    lire_config('backup_img/ftp_port',   '21'),
        (string) lire_config('backup_img/ftp_login',  ''),
        (string) lire_config('backup_img/ftp_pass',   ''),
        (string) lire_config('backup_img/ftp_dossier','backup_img/')
    );
}

/**
 * Ouvre une connexion FTP avec les paramètres fournis.
 * Si le dossier distant n'existe pas, tente de le créer automatiquement.
 * Retourne la ressource FTP ou false.
 */
function backup_img_ftp_connecter_avec(
    string $hote,
    int $port,
    string $login,
    string $pass,
    string $dossier
): mixed {
    if (!$hote || !$login) {
        spip_log('backup_img FTP: hôte ou login manquant', 'backup_img.' . _LOG_ERREUR);
        return false;
    }

    if (!function_exists('ftp_connect')) {
        spip_log('backup_img FTP: extension FTP PHP non disponible', 'backup_img.' . _LOG_ERREUR);
        return false;
    }

    $conn = @ftp_connect($hote, $port, 30);
    if (!$conn) {
        spip_log('backup_img FTP: connexion impossible à ' . $hote . ':' . $port, 'backup_img.' . _LOG_ERREUR);
        return false;
    }

    if (!@ftp_login($conn, $login, $pass)) {
        spip_log('backup_img FTP: authentification échouée pour ' . $login, 'backup_img.' . _LOG_ERREUR);
        ftp_close($conn);
        return false;
    }

    ftp_pasv($conn, true);

    if ($dossier && $dossier !== '/') {
        if (!@ftp_chdir($conn, $dossier)) {
            @ftp_mkdir($conn, $dossier);
            if (!@ftp_chdir($conn, $dossier)) {
                spip_log('backup_img FTP: impossible d\'accéder à ' . $dossier, 'backup_img.' . _LOG_ERREUR);
                ftp_close($conn);
                return false;
            }
            spip_log('backup_img FTP: dossier créé ' . $dossier, 'backup_img.' . _LOG_INFO_IMPORTANTE);
        }
    }

    return $conn;
}

/**
 * Upload un fichier local vers le FTP.
 * Retourne true en cas de succès.
 */
function backup_img_ftp_uploader(string $chemin_local, mixed $conn): bool
{
    $nom = basename($chemin_local);

    if (!is_file($chemin_local)) {
        spip_log('backup_img FTP: fichier introuvable ' . $chemin_local, 'backup_img.' . _LOG_ERREUR);
        return false;
    }

    $ok = @ftp_put($conn, $nom, $chemin_local, FTP_BINARY);
    if ($ok) {
        spip_log('backup_img FTP: upload réussi ' . $nom, 'backup_img.' . _LOG_INFO_IMPORTANTE);
    } else {
        spip_log('backup_img FTP: échec upload ' . $nom, 'backup_img.' . _LOG_ERREUR);
    }

    return $ok;
}

/**
 * Supprime un fichier sur le FTP.
 * Retourne true en cas de succès.
 */
function backup_img_ftp_supprimer(string $nom_fichier, mixed $conn): bool
{
    $ok = @ftp_delete($conn, $nom_fichier);
    if ($ok) {
        spip_log('backup_img FTP: supprimé ' . $nom_fichier, 'backup_img.' . _LOG_INFO_IMPORTANTE);
    } else {
        spip_log('backup_img FTP: échec suppression ' . $nom_fichier, 'backup_img.' . _LOG_AVERTISSEMENT);
    }

    return $ok;
}

/**
 * Liste les fichiers de backup sur le FTP (ceux qui correspondent au préfixe configuré).
 *
 * @return array<int, string> Tableau de noms de fichiers
 */
function backup_img_ftp_liste(mixed $conn): array
{
    $prefixe = (string) lire_config('backup_img/prefixe', 'backup_img');
    $rawlist = @ftp_nlist($conn, '.');

    if ($rawlist === false) {
        return [];
    }

    return array_filter(
        array_map('basename', $rawlist),
        fn(string $nom) => str_starts_with($nom, $prefixe . '_') && str_ends_with($nom, '.zip')
    );
}

/**
 * Teste la connexion FTP avec les paramètres fournis.
 * Retourne true si OK, 'created' si le dossier a été créé, ou une chaîne d'erreur.
 */
function backup_img_ftp_tester_connexion(
    string $hote,
    int $port,
    string $login,
    string $pass,
    string $dossier
): bool|string {
    if (!function_exists('ftp_connect')) {
        return 'Extension PHP FTP non disponible sur ce serveur';
    }

    if (!$hote) {
        return 'Hôte FTP vide';
    }

    $conn = @ftp_connect($hote, $port, 10);
    if (!$conn) {
        return 'Impossible de se connecter à ' . $hote . ':' . $port;
    }

    if (!@ftp_login($conn, $login, $pass)) {
        ftp_close($conn);
        return 'Authentification échouée pour l\'utilisateur ' . $login;
    }

    ftp_pasv($conn, true);

    $created = false;

    if ($dossier && $dossier !== '/') {
        if (!@ftp_chdir($conn, $dossier)) {
            @ftp_mkdir($conn, $dossier);
            if (!@ftp_chdir($conn, $dossier)) {
                ftp_close($conn);
                return 'Dossier distant inaccessible et non créable : ' . $dossier;
            }
            $created = true;
        }
    }

    ftp_close($conn);

    return $created ? 'created' : true;
}
