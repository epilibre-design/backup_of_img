<?php
if (!defined('_ECRIRE_INC_VERSION')) {
    return;
}

/**
 * Valide qu'un nom de fichier d'archive est sûr et accepté (zip ou tar).
 * Refuse tout nom contenant un séparateur de chemin.
 */
function backup_img_nom_valide(string $nom): bool
{
    if ($nom === '' || str_contains($nom, '/') || str_contains($nom, '\\')) {
        return false;
    }
    return (bool) preg_match('/\.(zip|tar)$/i', $nom);
}

/**
 * Génère le nom de fichier ZIP selon la configuration (préfixe + date).
 */
function backup_img_nom_fichier(string $format = 'zip'): string
{
    $prefixe     = lire_config('backup_img/prefixe',     'backup_img');
    $format_date = lire_config('backup_img/format_date', 'Ymd_His');
    $ext = ($format === 'tar') ? 'tar' : 'zip';
    return $prefixe . '_' . date($format_date) . '.' . $ext;
}

/**
 * Retourne le chemin absolu du dossier de stockage local, le crée si nécessaire.
 * Retourne false si le dossier est inaccessible.
 */
function backup_img_dossier_local(): string|false
{
    $dossier = lire_config('backup_img/dossier_local', 'tmp/backup_img/');

    if (!str_starts_with($dossier, '/')) {
        $dossier = _DIR_RACINE . $dossier;
    }

    $dossier = rtrim($dossier, '/') . '/';

    if (!is_dir($dossier) && !mkdir($dossier, 0755, true)) {
        spip_log('backup_img: impossible de créer ' . $dossier, 'backup_img.' . _LOG_ERREUR);
        return false;
    }

    return $dossier;
}

/**
 * Retourne le chemin du fichier JSON d'état pour un hash donné.
 */
function backup_img_chemin_etat(string $hash): string
{
    return _DIR_TMP . 'backup_img/' . $hash . '.json';
}

/**
 * Lit le fichier d'état JSON. Retourne null si absent ou JSON invalide.
 */
function backup_img_lire_etat(string $hash): array|null
{
    $chemin = backup_img_chemin_etat($hash);
    if (!is_file($chemin)) {
        return null;
    }
    $contenu = file_get_contents($chemin);
    if ($contenu === false) {
        return null;
    }
    $data = json_decode($contenu, true);
    if (!is_array($data)) {
        return null;
    }
    return $data;
}

/**
 * Merge $data dans l'état courant (ou crée), écrit le fichier.
 * Garantit que 'hash' est toujours présent.
 */
function backup_img_ecrire_etat(string $hash, array $data): void
{
    $chemin  = backup_img_chemin_etat($hash);
    $dossier = dirname($chemin);

    if (!is_dir($dossier)) {
        mkdir($dossier, 0755, true);
    }

    $etat         = backup_img_lire_etat($hash) ?? [];
    $etat         = array_merge($etat, $data);
    $etat['hash'] = $hash;

    file_put_contents($chemin, json_encode($etat, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

/**
 * Compte récursivement les fichiers (pas les dossiers) dans $dossier.
 */
function backup_img_compter_fichiers(string $dossier): int
{
    if (!is_dir($dossier)) {
        return 0;
    }

    $count = 0;
    $iter  = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dossier, FilesystemIterator::SKIP_DOTS)
    );
    foreach ($iter as $item) {
        if ($item->isFile()) {
            $count++;
        }
    }
    return $count;
}

/**
 * Retourne true si le binaire tar et proc_open sont disponibles sur ce serveur.
 */
function backup_img_tar_disponible(): bool
{
    if (!function_exists('proc_open')) {
        return false;
    }
    foreach (['/bin/tar', '/usr/bin/tar', '/usr/local/bin/tar'] as $path) {
        if (is_executable($path)) {
            return true;
        }
    }
    if (function_exists('exec')) {
        exec('which tar 2>/dev/null', $output, $code);
        return $code === 0 && !empty($output);
    }
    return false;
}

/**
 * Crée une archive TAR de IMG/ via proc_open(tar).
 * Mémoire constante quelle que soit la taille source.
 * Progression calculée d'après la taille du fichier en cours de création.
 * Retourne le chemin complet du TAR créé, ou false en cas d'échec.
 */
function backup_img_creer_tar(string $hash = ''): string|false
{
    $dossier = backup_img_dossier_local();
    if ($dossier === false) {
        if ($hash !== '') {
            backup_img_ecrire_etat($hash, ['state' => 'error', 'error' => 'Dossier local inaccessible', 'ended_at' => time()]);
        }
        return false;
    }

    $img_dir = rtrim(_DIR_IMG, '/');
    if (!is_dir($img_dir)) {
        spip_log('backup_img: dossier IMG/ introuvable : ' . $img_dir, 'backup_img.' . _LOG_ERREUR);
        if ($hash !== '') {
            backup_img_ecrire_etat($hash, ['state' => 'error', 'error' => 'Dossier IMG/ introuvable', 'ended_at' => time()]);
        }
        return false;
    }

    $nom    = backup_img_nom_fichier('tar');
    $chemin = $dossier . $nom;

    $taille_source = 0;
    if ($hash !== '') {
        $taille_source = backup_img_taille_dossier($img_dir);
        backup_img_ecrire_etat($hash, [
            'state'      => 'running',
            'total'      => $taille_source,
            'processed'  => 0,
            'percent'    => 0,
            'started_at' => time(),
        ]);
    }

    $parent  = dirname($img_dir);
    $dirname = basename($img_dir);
    $cmd     = 'tar cf ' . escapeshellarg($chemin)
             . ' -C ' . escapeshellarg($parent)
             . ' ' . escapeshellarg($dirname);

    $descriptorspec = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];

    $process = proc_open($cmd, $descriptorspec, $pipes);
    if (!is_resource($process)) {
        spip_log('backup_img: proc_open(tar) échoué', 'backup_img.' . _LOG_ERREUR);
        if ($hash !== '') {
            backup_img_ecrire_etat($hash, ['state' => 'error', 'error' => 'proc_open(tar) indisponible', 'ended_at' => time()]);
        }
        return false;
    }

    fclose($pipes[0]);
    fclose($pipes[1]);

    // Suivi de la progression via la taille de l'archive en cours de création
    if ($hash !== '' && $taille_source > 0) {
        while (proc_get_status($process)['running']) {
            clearstatcache(true, $chemin);
            $archive_size = is_file($chemin) ? (int) filesize($chemin) : 0;
            backup_img_ecrire_etat($hash, [
                'processed' => $archive_size,
                'percent'   => min(99, (int) ($archive_size / $taille_source * 100)),
            ]);
            sleep(1);
        }
    }

    $stderr    = stream_get_contents($pipes[2]);
    fclose($pipes[2]);
    $exit_code = proc_close($process);

    // GNU tar : code 0 = succès, 1 = avertissements (fichiers modifiés, etc.) mais archive valide, 2+ = erreur fatale
    $archive_valide = is_file($chemin) && filesize($chemin) > 0;
    if ($exit_code >= 2 || !$archive_valide) {
        $err = trim($stderr ?: 'Code de sortie fatal ' . $exit_code);
        spip_log('backup_img: tar échoué — ' . $err, 'backup_img.' . _LOG_ERREUR);
        if ($hash !== '') {
            backup_img_ecrire_etat($hash, ['state' => 'error', 'error' => 'tar : ' . $err, 'ended_at' => time()]);
        }
        return false;
    }
    if ($exit_code === 1) {
        $warn = trim($stderr ?: '');
        spip_log('backup_img: tar terminé avec avertissements (code 1)' . ($warn ? ' — ' . $warn : ''), 'backup_img.' . _LOG_INFO_IMPORTANTE);
    }

    if ($hash !== '') {
        backup_img_ecrire_etat($hash, [
            'state'     => 'done',
            'nom'       => basename($chemin),
            'percent'   => 100,
            'ended_at'  => time(),
        ]);
    }

    spip_log('backup_img: TAR créé ' . $chemin, 'backup_img.' . _LOG_INFO_IMPORTANTE);

    return $chemin;
}

/**
 * Crée une archive selon le format configuré (zip ou tar).
 * Fallback automatique sur zip si tar est demandé mais indisponible.
 */
function backup_img_creer_archive(string $hash = ''): string|false
{
    $format = lire_config('backup_img/format', 'zip');
    if ($format === 'tar' && backup_img_tar_disponible()) {
        return backup_img_creer_tar($hash);
    }
    return backup_img_creer_zip($hash);
}

/**
 * Crée le ZIP de IMG/ dans le dossier local.
 * Si $hash est fourni, met à jour l'état asynchrone pendant le traitement.
 * Retourne le chemin complet du ZIP créé, ou false en cas d'échec.
 */
function backup_img_creer_zip(string $hash = ''): string|false
{
    if (!class_exists('ZipArchive')) {
        spip_log('backup_img: extension ZipArchive manquante', 'backup_img.' . _LOG_ERREUR);
        if ($hash !== '') {
            backup_img_ecrire_etat($hash, ['state' => 'error', 'error' => 'Extension ZipArchive manquante', 'ended_at' => time()]);
        }
        return false;
    }

    $dossier = backup_img_dossier_local();
    if ($dossier === false) {
        if ($hash !== '') {
            backup_img_ecrire_etat($hash, ['state' => 'error', 'error' => 'Dossier local inaccessible', 'ended_at' => time()]);
        }
        return false;
    }

    $nom     = backup_img_nom_fichier();
    $chemin  = $dossier . $nom;
    $img_dir = rtrim(_DIR_IMG, '/');

    if (!is_dir($img_dir)) {
        spip_log('backup_img: dossier IMG/ introuvable : ' . $img_dir, 'backup_img.' . _LOG_ERREUR);
        if ($hash !== '') {
            backup_img_ecrire_etat($hash, ['state' => 'error', 'error' => 'Dossier IMG/ introuvable', 'ended_at' => time()]);
        }
        return false;
    }

    if ($hash !== '') {
        $total = backup_img_compter_fichiers($img_dir);
        backup_img_ecrire_etat($hash, ['state' => 'running', 'total' => $total, 'processed' => 0, 'percent' => 0, 'started_at' => time()]);
    }

    $zip = new ZipArchive();
    if ($zip->open($chemin, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        spip_log('backup_img: impossible d\'ouvrir le ZIP ' . $chemin, 'backup_img.' . _LOG_ERREUR);
        if ($hash !== '') {
            backup_img_ecrire_etat($hash, ['state' => 'error', 'error' => 'Impossible d\'ouvrir le ZIP', 'ended_at' => time()]);
        }
        return false;
    }

    $compteur = 0;
    backup_img_zip_ajouter_dossier($zip, $img_dir, 'IMG', $hash, $total ?? 0, $compteur);

    $zip->close();

    if ($hash !== '') {
        backup_img_ecrire_etat($hash, ['state' => 'done', 'nom' => basename($chemin), 'percent' => 100, 'processed' => $compteur, 'ended_at' => time()]);
    }

    spip_log('backup_img: ZIP créé ' . $chemin, 'backup_img.' . _LOG_INFO_IMPORTANTE);

    return $chemin;
}

/**
 * Ajoute récursivement un dossier dans le ZIP.
 * Si $hash est fourni, met à jour la progression toutes les 50 entrées.
 */
function backup_img_zip_ajouter_dossier(ZipArchive $zip, string $dossier, string $dossier_zip, string $hash = '', int $total = 0, int &$compteur = 0): void
{
    $items = scandir($dossier);
    if ($items === false) {
        return;
    }

    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }

        $chemin_complet = $dossier . '/' . $item;
        $chemin_zip     = $dossier_zip . '/' . $item;

        if (is_dir($chemin_complet)) {
            $zip->addEmptyDir($chemin_zip);
            backup_img_zip_ajouter_dossier($zip, $chemin_complet, $chemin_zip, $hash, $total, $compteur);
        } elseif (is_file($chemin_complet)) {
            $zip->addFile($chemin_complet, $chemin_zip);
            $compteur++;
            if ($hash !== '' && $total > 0 && $compteur % 50 === 0) {
                backup_img_ecrire_etat($hash, [
                    'processed' => $compteur,
                    'percent'   => min(99, (int)($compteur / $total * 100)),
                ]);
            }
        }
    }
}

/**
 * Retourne la liste des backups locaux, triés du plus ancien au plus récent.
 * Chaque entrée : ['chemin' => string, 'nom' => string, 'taille' => int, 'mtime' => int]
 *
 * @return array<int, array{chemin: string, nom: string, taille: int, mtime: int}>
 */
function backup_img_liste_locale(): array
{
    $dossier = backup_img_dossier_local();
    if ($dossier === false) {
        return [];
    }

    $prefixe = lire_config('backup_img/prefixe', 'backup_img');
    $fichiers = array_merge(
        glob($dossier . $prefixe . '_*.zip') ?: [],
        glob($dossier . $prefixe . '_*.tar') ?: []
    );

    $liste = [];
    foreach ($fichiers as $chemin) {
        if (!is_file($chemin)) {
            continue;
        }
        $liste[] = [
            'chemin' => $chemin,
            'nom'    => basename($chemin),
            'taille' => (int) filesize($chemin),
            'mtime'  => (int) filemtime($chemin),
        ];
    }

    usort($liste, fn($a, $b) => $a['mtime'] <=> $b['mtime']);

    return $liste;
}

/**
 * Retourne la taille en octets d'un dossier (récursif).
 */
function backup_img_taille_dossier(string $dossier): int
{
    if (!is_dir($dossier)) {
        return 0;
    }

    $taille = 0;
    $iter = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dossier, FilesystemIterator::SKIP_DOTS)
    );
    foreach ($iter as $item) {
        if ($item->isFile()) {
            $taille += $item->getSize();
        }
    }
    return $taille;
}

/**
 * Retourne la taille totale des backups locaux en octets.
 */
function backup_img_taille_totale(): int
{
    $total = 0;
    foreach (backup_img_liste_locale() as $f) {
        $total += $f['taille'];
    }
    return $total;
}

/**
 * Applique la rotation : supprime les backups les plus anciens si le nombre
 * ou l'espace dépasse les limites configurées. Supprime aussi sur FTP si actif.
 */
function backup_img_rotation(): void
{
    $max_count  = (int) lire_config('backup_img/max_sauvegardes', '10');
    $max_mo     = (int) lire_config('backup_img/max_espace_mo',   '500');
    $max_octets = $max_mo * 1024 * 1024;

    $ftp_actif = (int) lire_config('backup_img/ftp_actif', '0');
    $conn_ftp  = null;

    if ($ftp_actif) {
        include_spip('inc/backup_img_ftp');
        $conn_ftp = backup_img_ftp_connecter();
    }

    $liste = backup_img_liste_locale();

    while (
        count($liste) > 1
        && (count($liste) > $max_count || backup_img_taille_totale() > $max_octets)
    ) {
        if (empty($liste)) {
            break;
        }

        $plus_ancien = array_shift($liste);
        backup_img_supprimer_fichier($plus_ancien['chemin'], $plus_ancien['nom'], $conn_ftp);

        $liste = backup_img_liste_locale();
    }

    if ($conn_ftp) {
        ftp_close($conn_ftp);
    }
}

/**
 * Supprime un fichier de sauvegarde en local et optionnellement sur FTP.
 */
function backup_img_supprimer_fichier(string $chemin_local, string $nom, mixed $conn_ftp = null): void
{
    if (is_file($chemin_local)) {
        unlink($chemin_local);
        spip_log('backup_img: supprimé local ' . $nom, 'backup_img.' . _LOG_INFO_IMPORTANTE);
    }

    if ($conn_ftp !== null) {
        include_spip('inc/backup_img_ftp');
        backup_img_ftp_supprimer($nom, $conn_ftp);
    }
}

/**
 * Exécute le job de backup identifié par $hash.
 * Ne fait rien si l'état n'est pas 'pending'.
 */
function backup_img_executer_job(string $hash): void
{
    $etat = backup_img_lire_etat($hash);
    if (!$etat || $etat['state'] !== 'pending') {
        return;
    }

    set_time_limit(0);
    ignore_user_abort(true);

    $max_mo     = (int) lire_config('backup_img/max_espace_mo', '500');
    $max_octets = $max_mo * 1024 * 1024;
    $taille_img = backup_img_taille_dossier(rtrim(_DIR_IMG, '/'));

    if ($taille_img > $max_octets) {
        $taille_img_mo = (int) round($taille_img / (1024 * 1024));
        spip_log(
            "backup_img: espace insuffisant — IMG={$taille_img_mo} Mo, limite={$max_mo} Mo",
            'backup_img.' . _LOG_AVERTISSEMENT
        );
        backup_img_ecrire_etat($hash, [
            'state'    => 'error',
            'error'    => "Espace insuffisant : IMG={$taille_img_mo} Mo, limite={$max_mo} Mo",
            'ended_at' => time(),
        ]);
        return;
    }

    $chemin = backup_img_creer_archive($hash);

    if (!$chemin) {
        backup_img_ecrire_etat($hash, ['state' => 'error', 'error' => 'Échec création archive', 'ended_at' => time()]);
        return;
    }

    backup_img_rotation();

    $ftp_actif = (int) lire_config('backup_img/ftp_actif', '0');
    if ($ftp_actif) {
        include_spip('inc/backup_img_ftp');
        $conn = backup_img_ftp_connecter();
        if ($conn) {
            backup_img_ftp_uploader($chemin, $conn);
            ftp_close($conn);
        }
    }
}
