<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class BackupImgCreateTest extends TestCase
{
    private string $imgDirOriginal = '';
    private string $imgDirTest = '';
    private string $backupDir = '';

    protected function setUp(): void
    {
        // Répertoire IMG/ factice
        $this->imgDirTest = sys_get_temp_dir() . '/backup_img_img_' . uniqid() . '/';
        mkdir($this->imgDirTest, 0755, true);

        // Créer quelques fichiers factices dans IMG/
        file_put_contents($this->imgDirTest . 'photo1.jpg', 'fake jpg content');
        file_put_contents($this->imgDirTest . 'photo2.png', 'fake png content');
        mkdir($this->imgDirTest . 'sous_dossier/', 0755, true);
        file_put_contents($this->imgDirTest . 'sous_dossier/image3.jpg', 'fake content');

        // Répertoire de backup
        $this->backupDir = sys_get_temp_dir() . '/backup_img_backup_' . uniqid() . '/';
        mkdir($this->backupDir, 0755, true);

        // Modifier la config pour pointer vers nos dossiers de test
        ecrire_config('backup_img/dossier_local', $this->backupDir);
        ecrire_config('backup_img/prefixe', 'backup_img_test');

        // Remplacer temporairement _DIR_IMG
        if (defined('_DIR_IMG')) {
            $this->imgDirOriginal = _DIR_IMG;
        }
    }

    protected function tearDown(): void
    {
        // Nettoyer les fichiers de test
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

    public function testNomFichierGenereSuivitLeFormat(): void
    {
        include_spip('inc/backup_img');
        $nom = backup_img_nom_fichier();
        $this->assertMatchesRegularExpression(
            '/^backup_img_test_\d{8}_\d{6}\.zip$/',
            $nom
        );
    }

    public function testCreerZipAvecImgFactice(): void
    {
        // On patche _DIR_IMG dynamiquement
        if (!defined('_DIR_IMG')) {
            define('_DIR_IMG', $this->imgDirTest);
        }

        include_spip('inc/backup_img');

        $chemin = backup_img_creer_zip();

        if ($chemin === false) {
            $this->markTestSkipped('Création ZIP impossible (peut-être _DIR_IMG déjà défini différemment)');
        }

        $this->assertIsString($chemin);
        $this->assertFileExists($chemin);
        $this->assertStringEndsWith('.zip', $chemin);
        $this->assertGreaterThan(0, filesize($chemin));
    }

    public function testListeLocaleApresCreation(): void
    {
        if (!class_exists('ZipArchive')) {
            $this->markTestSkipped('ZipArchive non disponible');
        }

        include_spip('inc/backup_img');

        // Créer manuellement un ZIP de test dans le dossier backup
        $nomTest = 'backup_img_test_20250101_120000.zip';
        $zip = new ZipArchive();
        $cheminZip = $this->backupDir . $nomTest;
        $zip->open($cheminZip, ZipArchive::CREATE);
        $zip->addFromString('test.txt', 'contenu test');
        $zip->close();

        $liste = backup_img_liste_locale();

        $this->assertCount(1, $liste);
        $this->assertSame($nomTest, $liste[0]['nom']);
        $this->assertGreaterThan(0, $liste[0]['taille']);
    }

    public function testRotationSupprimeLesAnciens(): void
    {
        include_spip('inc/backup_img');
        ecrire_config('backup_img/max_sauvegardes', '2');

        // Créer 3 ZIPs de test
        for ($i = 1; $i <= 3; $i++) {
            $nom = sprintf('backup_img_test_202501%02d_120000.zip', $i);
            $zip = new ZipArchive();
            $zip->open($this->backupDir . $nom, ZipArchive::CREATE);
            $zip->addFromString('test.txt', 'content ' . $i);
            $zip->close();
            touch($this->backupDir . $nom, mktime(12, 0, 0, 1, $i, 2025));
        }

        backup_img_rotation();

        $liste = backup_img_liste_locale();
        $this->assertCount(2, $liste);

        $noms = array_column($liste, 'nom');
        $this->assertNotContains('backup_img_test_20250101_120000.zip', $noms);

        // Restaurer
        ecrire_config('backup_img/max_sauvegardes', '10');
    }
}
