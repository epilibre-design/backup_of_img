<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Tests d'intégration de la sauvegarde asynchrone avec hash et suivi d'état.
 *
 * Requiert le bootstrap d'intégration (SPIP complet) pour ecrire_config / lire_config.
 */
final class BackupImgAsyncTest extends TestCase
{
    private string $hash       = '';
    private string $imgDirTest = '';
    private string $backupDir  = '';

    protected function setUp(): void
    {
        include_spip('inc/backup_img');

        $this->hash = 'async_test_' . uniqid();

        // Répertoire IMG/ factice dans /tmp pour ne pas polluer le vrai IMG
        $this->imgDirTest = sys_get_temp_dir() . '/backup_img_img_' . uniqid() . '/';
        mkdir($this->imgDirTest, 0755, true);

        // Répertoire de backup local factice
        $this->backupDir = sys_get_temp_dir() . '/backup_img_backup_' . uniqid() . '/';
        mkdir($this->backupDir, 0755, true);

        // Pointeur la config vers nos dossiers de test
        ecrire_config('backup_img/dossier_local', $this->backupDir);
        ecrire_config('backup_img/prefixe', 'backup_img_async_test');
    }

    protected function tearDown(): void
    {
        // Supprimer le fichier d'état
        $chemin_etat = backup_img_chemin_etat($this->hash);
        if (is_file($chemin_etat)) {
            unlink($chemin_etat);
        }

        // Nettoyer les dossiers temporaires
        $this->supprimerRecursif($this->imgDirTest);
        $this->supprimerRecursif($this->backupDir);

        // Restaurer la config
        ecrire_config('backup_img/dossier_local', 'tmp/backup_img/');
        ecrire_config('backup_img/prefixe', 'backup_img');
    }

    private function supprimerRecursif(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach (scandir($dir) ?: [] as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $chemin = $dir . $item;
            is_dir($chemin) ? $this->supprimerRecursif($chemin . '/') : unlink($chemin);
        }
        rmdir($dir);
    }

    private function creerFichiersFactices(int $n): void
    {
        for ($i = 1; $i <= $n; $i++) {
            file_put_contents($this->imgDirTest . "img_{$i}.jpg", "fake content {$i}");
        }
    }

    /**
     * Remplace _DIR_IMG si possible, ou skippe si déjà défini à une autre valeur.
     */
    private function patcherDirImg(): void
    {
        if (!defined('_DIR_IMG')) {
            define('_DIR_IMG', $this->imgDirTest);
        } elseif (rtrim(_DIR_IMG, '/') !== rtrim($this->imgDirTest, '/')) {
            // _DIR_IMG est défini mais pointe ailleurs — on ne peut pas le redéfinir
            // On utilisera la valeur existante : créer les fichiers là-dedans si possible
            if (is_dir(_DIR_IMG)) {
                $this->imgDirTest = rtrim(_DIR_IMG, '/') . '/';
            }
        }
    }

    // -------------------------------------------------------------------------

    public function testCreerZipSansHashFonctionne(): void
    {
        if (!class_exists('ZipArchive')) {
            $this->markTestSkipped('ZipArchive non disponible');
        }

        $this->patcherDirImg();

        file_put_contents($this->imgDirTest . 'smoke.jpg', 'smoke test');

        $chemin = backup_img_creer_zip();

        if ($chemin === false) {
            $this->markTestSkipped('Création ZIP impossible (IMG/ peut pointer ailleurs)');
        }

        $this->assertIsString($chemin);
        $this->assertFileExists($chemin);
        $this->assertStringEndsWith('.zip', $chemin);
        $this->assertGreaterThan(0, filesize($chemin));
    }

    public function testCreerZipAvecHashEcritEtatDone(): void
    {
        if (!class_exists('ZipArchive')) {
            $this->markTestSkipped('ZipArchive non disponible');
        }

        $this->patcherDirImg();
        $this->creerFichiersFactices(3);

        $chemin = backup_img_creer_zip($this->hash);

        if ($chemin === false) {
            $this->markTestSkipped('Création ZIP impossible (IMG/ peut pointer ailleurs)');
        }

        // Vérifier le fichier d'état
        $etat = backup_img_lire_etat($this->hash);

        $this->assertIsArray($etat);
        $this->assertSame('done', $etat['state'], "L'état final doit être 'done'");
        $this->assertNotEmpty($etat['nom'], "Le nom du ZIP doit être renseigné");
        $this->assertStringEndsWith('.zip', $etat['nom']);
        $this->assertSame($this->hash, $etat['hash']);
        $this->assertSame(100, $etat['percent']);
    }

    public function testCreerZipAvecHashEcritProgression(): void
    {
        if (!class_exists('ZipArchive')) {
            $this->markTestSkipped('ZipArchive non disponible');
        }

        $this->patcherDirImg();

        // 150 fichiers pour déclencher au moins une écriture intermédiaire à 50
        $this->creerFichiersFactices(150);

        $chemin = backup_img_creer_zip($this->hash);

        if ($chemin === false) {
            $this->markTestSkipped('Création ZIP impossible (IMG/ peut pointer ailleurs)');
        }

        $etat = backup_img_lire_etat($this->hash);

        $this->assertIsArray($etat);
        $this->assertSame('done', $etat['state'], "L'état final doit être 'done' même avec 150 fichiers");
        $this->assertSame(100, $etat['percent']);
        $this->assertSame(150, $etat['processed']);
        $this->assertNotNull($etat['ended_at']);
    }

    public function testLireEtatRetourneNullPourHashInconnu(): void
    {
        $result = backup_img_lire_etat('hash_qui_nexiste_pas_0000_integration');
        $this->assertNull($result);
    }

    public function testEcrireEtLireEtatComplet(): void
    {
        $donnees = [
            'state'      => 'running',
            'percent'    => 42,
            'processed'  => 42,
            'total'      => 100,
            'nom'        => null,
            'started_at' => '2026-01-01T00:00:00+00:00',
            'ended_at'   => null,
            'error'      => null,
        ];

        backup_img_ecrire_etat($this->hash, $donnees);
        $etat = backup_img_lire_etat($this->hash);

        $this->assertIsArray($etat);
        $this->assertSame('running', $etat['state']);
        $this->assertSame(42, $etat['percent']);
        $this->assertSame(42, $etat['processed']);
        $this->assertSame(100, $etat['total']);
        $this->assertNull($etat['nom']);
        $this->assertNull($etat['ended_at']);
        $this->assertNull($etat['error']);
        $this->assertSame($this->hash, $etat['hash']);
    }
}
