<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2) . '/inc/backup_img.php';

/**
 * Tests unitaires des fonctions de gestion de l'état asynchrone.
 *
 * Le bootstrap tests/bootstrap.php définit _DIR_TMP (sys_get_temp_dir()/spip_test_tmp/),
 * ce qui suffit à faire tourner ces tests sans SPIP complet.
 */
final class BackupImgEtatTest extends TestCase
{
    private string $hash = 'testhash_etat_unit';

    protected function tearDown(): void
    {
        $chemin = backup_img_chemin_etat($this->hash);
        if (is_file($chemin)) {
            unlink($chemin);
        }
        // Dossier éventuellement créé par backup_img_ecrire_etat
        $dossier = dirname($chemin);
        if (is_dir($dossier) && count(scandir($dossier)) === 2) {
            rmdir($dossier);
        }
    }

    public function testCheminEtatRetourneCheminCorrect(): void
    {
        $chemin = backup_img_chemin_etat('abc123');

        $this->assertStringContainsString('abc123.json', $chemin);
        $this->assertStringContainsString('backup_img', $chemin);
    }

    public function testEcrireEtatCreeLeFichier(): void
    {
        backup_img_ecrire_etat($this->hash, ['state' => 'pending']);

        $chemin = backup_img_chemin_etat($this->hash);
        $this->assertFileExists($chemin);
    }

    public function testLireEtatRetourneNullSiAbsent(): void
    {
        $result = backup_img_lire_etat('hash_inexistant_000000000');
        $this->assertNull($result);
    }

    public function testEcrireEtatMerge(): void
    {
        backup_img_ecrire_etat($this->hash, ['state' => 'pending', 'percent' => 0]);
        backup_img_ecrire_etat($this->hash, ['percent' => 50, 'state' => 'running']);

        $etat = backup_img_lire_etat($this->hash);

        $this->assertIsArray($etat);
        $this->assertSame('running', $etat['state']);
        $this->assertSame(50, $etat['percent']);
        $this->assertArrayHasKey('hash', $etat);
    }

    public function testEcrireEtatGarantitHash(): void
    {
        backup_img_ecrire_etat($this->hash, ['state' => 'pending']);

        $etat = backup_img_lire_etat($this->hash);

        $this->assertIsArray($etat);
        $this->assertSame($this->hash, $etat['hash']);
    }

    public function testCompterFichiersVideRetourne0(): void
    {
        $dossier = sys_get_temp_dir() . '/backup_img_vide_' . uniqid() . '/';
        mkdir($dossier, 0755, true);

        try {
            $count = backup_img_compter_fichiers($dossier);
            $this->assertSame(0, $count);
        } finally {
            rmdir($dossier);
        }
    }

    public function testCompterFichiers(): void
    {
        $dossier = sys_get_temp_dir() . '/backup_img_count_' . uniqid() . '/';
        mkdir($dossier, 0755, true);
        file_put_contents($dossier . 'fichier1.txt', 'a');
        file_put_contents($dossier . 'fichier2.txt', 'b');
        file_put_contents($dossier . 'fichier3.txt', 'c');

        try {
            $count = backup_img_compter_fichiers($dossier);
            $this->assertSame(3, $count);
        } finally {
            foreach (glob($dossier . '*') ?: [] as $f) {
                unlink($f);
            }
            rmdir($dossier);
        }
    }

    public function testCompterFichiersNeComptePasLesDossiers(): void
    {
        $dossier = sys_get_temp_dir() . '/backup_img_nodir_' . uniqid() . '/';
        mkdir($dossier, 0755, true);
        mkdir($dossier . 'sous_dossier/', 0755, true);
        file_put_contents($dossier . 'fichier1.txt', 'a');
        file_put_contents($dossier . 'sous_dossier/fichier2.txt', 'b');

        try {
            $count = backup_img_compter_fichiers($dossier);
            $this->assertSame(2, $count);
        } finally {
            unlink($dossier . 'fichier1.txt');
            unlink($dossier . 'sous_dossier/fichier2.txt');
            rmdir($dossier . 'sous_dossier/');
            rmdir($dossier);
        }
    }
}
