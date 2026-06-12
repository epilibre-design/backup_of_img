<?php
if (!defined('_ECRIRE_INC_VERSION')) {
    return;
}

/**
 * Génère le nom de fichier ZIP selon la configuration (préfixe + date).
 */
function backup_img_nom_fichier(): string
{
    $prefixe     = lire_config('backup_img/prefixe',     'backup_img');
    $format_date = lire_config('backup_img/format_date', 'Ymd_His');

    return $prefixe . '_' . date($format_date) . '.zip';
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
 * Crée le ZIP de IMG/ dans le dossier local.
 * Si $hash est fourni, écrit l'état d'avancement dans un fichier JSON.
 * Retourne le chemin complet du ZIP créé, ou false en cas d'échec.
 */
function backup_img_creer_zip(string $hash = ''): string|false
{
    if (!class_exists('ZipArchive')) {
        spip_log('backup_img: extension ZipArchive manquante', 'backup_img.' . _LOG_ERREUR);
        if ($hash) {
            backup_img_ecrire_etat($hash, ['state' => 'error', 'error' => 'ZipArchive manquante']);
        }
        return false;
    }

    $dossier = backup_img_dossier_local();
    if ($dossier === false) {
        if ($hash) {
            backup_img_ecrire_etat($hash, ['state' => 'error', 'error' => 'Dossier local inaccessible']);
        }
        return false;
    }

    $nom     = backup_img_nom_fichier();
    $chemin  = $dossier . $nom;
    $img_dir = rtrim(_DIR_IMG, '/');

    if (!is_dir($img_dir)) {
        spip_log('backup_img: dossier IMG/ introuvable : ' . $img_dir, 'backup_img.' . _LOG_ERREUR);
        if ($hash) {
            backup_img_ecrire_etat($hash, ['state' => 'error', 'error' => 'IMG/ introuvable']);
        }
        return false;
    }

    $total = $hash ? backup_img_compter_fichiers($img_dir) : 0;

    if ($hash) {
        backup_img_ecrire_etat($hash, [
            'state'      => 'running',
            'percent'    => 0,
            'processed'  => 0,
            'total'      => $total,
            'nom'        => null,
            'started_at' => date('c'),
            'ended_at'   => null,
            'error'      => null,
        ]);
    }

    $zip = new ZipArchive();
    if ($zip->open($chemin, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        spip_log('backup_img: impossible d\'ouvrir le ZIP ' . $chemin, 'backup_img.' . _LOG_ERREUR);
        if ($hash) {
            backup_img_ecrire_etat($hash, ['state' => 'error', 'error' => "Impossible d'ouvrir le ZIP"]);
        }
        return false;
    }

    if ($hash) {
        $processed    = 0;
        $last_percent = 0;
        $iter         = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($img_dir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($iter as $item) {
            $chemin_rel = 'IMG/' . $iter->getSubPathName();
            if ($item->isDir()) {
                $zip->addEmptyDir($chemin_rel);
            } else {
                $zip->addFile($item->getPathname(), $chemin_rel);
                $processed++;
                $percent = $total > 0 ? (int) round($processed / $total * 100) : 0;
                if ($processed % 50 === 0 || $percent >= $last_percent + 10) {
                    backup_img_ecrire_etat($hash, [
                        'processed' => $processed,
                        'percent'   => $percent,
                    ]);
                    $last_percent = $percent;
                }
            }
        }
    } else {
        $processed = 0;
        backup_img_zip_ajouter_dossier($zip, $img_dir, 'IMG');
    }

    $zip->close();

    if ($hash) {
        backup_img_ecrire_etat($hash, [
            'state'     => 'done',
            'percent'   => 100,
            'processed' => $processed,
            'nom'       => $nom,
            'ended_at'  => date('c'),
        ]);
    }

    spip_log('backup_img: ZIP créé ' . $chemin, 'backup_img.' . _LOG_INFO_IMPORTANTE);

    return $chemin;
}

/**
 * Ajoute récursivement un dossier dans le ZIP.
 */
function backup_img_zip_ajouter_dossier(ZipArchive $zip, string $dossier, string $dossier_zip): void
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
            backup_img_zip_ajouter_dossier($zip, $chemin_complet, $chemin_zip);
        } elseif (is_file($chemin_complet)) {
            $zip->addFile($chemin_complet, $chemin_zip);
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
    $fichiers = glob($dossier . $prefixe . '_*.zip');
    if ($fichiers === false) {
        return [];
    }

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
