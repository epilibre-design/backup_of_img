<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2) . '/inc/backup_img.php';
require_once dirname(__DIR__, 2) . '/backup_img_fonctions.php';

final class BackupImgRotationTest extends TestCase
{
    private string $tmpDir = '';

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/backup_img_test_' . uniqid() . '/';
        mkdir($this->tmpDir, 0755, true);
        $GLOBALS['_test_config'] = [
            'backup_img/prefixe'         => 'backup_img',
            'backup_img/dossier_local'   => $this->tmpDir,
            'backup_img/max_sauvegardes' => '3',
            'backup_img/max_espace_mo'   => '500',
            'backup_img/ftp_actif'       => '0',
        ];
    }

    protected function tearDown(): void
    {
        foreach (glob($this->tmpDir . '*.zip') ?: [] as $f) {
            @unlink($f);
        }
        @rmdir($this->tmpDir);
    }

    private function creerFichierTest(string $nom, int $taille = 100): string
    {
        $chemin = $this->tmpDir . $nom;
        file_put_contents($chemin, str_repeat('x', $taille));
        return $chemin;
    }

    public function testListeLocaleRetourneTableauVide(): void
    {
        $liste = backup_img_liste_locale();
        $this->assertIsArray($liste);
        $this->assertEmpty($liste);
    }

    public function testListeLocaleTrouveLesBackups(): void
    {
        $this->creerFichierTest('backup_img_20250101_120000.zip');
        $this->creerFichierTest('backup_img_20250102_120000.zip');

        $liste = backup_img_liste_locale();

        $this->assertCount(2, $liste);
        $this->assertArrayHasKey('nom', $liste[0]);
        $this->assertArrayHasKey('taille', $liste[0]);
        $this->assertArrayHasKey('mtime', $liste[0]);
        $this->assertArrayHasKey('chemin', $liste[0]);
    }

    public function testListeLocaleNeTrouvePasLesNonZip(): void
    {
        $this->creerFichierTest('backup_img_test.txt');
        $this->creerFichierTest('backup_img_20250101_120000.zip');

        $liste = backup_img_liste_locale();

        $this->assertCount(1, $liste);
        $this->assertSame('backup_img_20250101_120000.zip', $liste[0]['nom']);
    }

    public function testTailleTotale(): void
    {
        $this->creerFichierTest('backup_img_20250101_120000.zip', 1000);
        $this->creerFichierTest('backup_img_20250102_120000.zip', 2000);

        $taille = backup_img_taille_totale();
        $this->assertSame(3000, $taille);
    }

    public function testRotationSupprimeLePlusAncienSiCountDepasse(): void
    {
        // Créer 4 backups mais max = 3
        for ($i = 1; $i <= 4; $i++) {
            $chemin = $this->creerFichierTest(sprintf('backup_img_202501%02d_120000.zip', $i));
            touch($chemin, mktime(12, 0, 0, 1, $i, 2025));
        }

        $GLOBALS['_test_config']['backup_img/max_sauvegardes'] = '3';
        backup_img_rotation();

        $liste = backup_img_liste_locale();
        $this->assertCount(3, $liste);

        // Le plus ancien (01) doit être supprimé
        $noms = array_column($liste, 'nom');
        $this->assertNotContains('backup_img_20250101_120000.zip', $noms);
        $this->assertContains('backup_img_20250102_120000.zip', $noms);
    }

    public function testRotationSupprimeSiEspaceDepasse(): void
    {
        // max_espace_mo = 1 Mo = 1048576 octets
        // Créer 3 fichiers de 400Ko chacun = 1.2 Mo > 1 Mo
        $GLOBALS['_test_config']['backup_img/max_espace_mo']   = '1';
        $GLOBALS['_test_config']['backup_img/max_sauvegardes'] = '100';

        $chemin1 = $this->creerFichierTest('backup_img_20250101_120000.zip', 400 * 1024);
        $chemin2 = $this->creerFichierTest('backup_img_20250102_120000.zip', 400 * 1024);
        $chemin3 = $this->creerFichierTest('backup_img_20250103_120000.zip', 400 * 1024);
        touch($chemin1, mktime(12, 0, 0, 1, 1, 2025));
        touch($chemin2, mktime(12, 0, 0, 1, 2, 2025));
        touch($chemin3, mktime(12, 0, 0, 1, 3, 2025));

        backup_img_rotation();

        $liste = backup_img_liste_locale();
        // L'espace total des restants doit être <= 1 Mo
        $this->assertLessThanOrEqual(1 * 1024 * 1024, backup_img_taille_totale());
    }

    public function testFormateurTaille(): void
    {
        $this->assertSame('500 o', backup_img_formater_taille(500));
        $this->assertSame('1 Ko', backup_img_formater_taille(1024));
        $this->assertSame('1.5 Ko', backup_img_formater_taille(1536));
        $this->assertSame('1 Mo', backup_img_formater_taille(1048576));
        $this->assertSame('1 Go', backup_img_formater_taille(1073741824));
    }
}
