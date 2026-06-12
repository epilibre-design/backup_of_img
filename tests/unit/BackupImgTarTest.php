<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2) . '/inc/backup_img.php';
require_once dirname(__DIR__, 2) . '/backup_img_fonctions.php';

final class BackupImgTarTest extends TestCase
{
    protected function setUp(): void
    {
        $GLOBALS['_test_config'] = [];
    }

    public function testTarDisponibleRetourneBooleen(): void
    {
        if (!function_exists('backup_img_tar_disponible')) {
            $this->markTestSkipped('Fonction backup_img_tar_disponible() absente');
        }

        $result = backup_img_tar_disponible();
        $this->assertIsBool($result);
    }

    public function testNomFichierFormatTarRetourneExtensionTar(): void
    {
        if (!function_exists('lire_config')) {
            $this->markTestSkipped('lire_config() non disponible (SPIP non chargé)');
        }

        $nom = backup_img_nom_fichier('tar');
        $this->assertStringEndsWith('.tar', $nom);
    }

    public function testNomFichierFormatZipRetourneExtensionZip(): void
    {
        if (!function_exists('lire_config')) {
            $this->markTestSkipped('lire_config() non disponible (SPIP non chargé)');
        }

        $nom = backup_img_nom_fichier('zip');
        $this->assertStringEndsWith('.zip', $nom);
    }

    public function testNomFichierDefautRetourneZip(): void
    {
        if (!function_exists('lire_config')) {
            $this->markTestSkipped('lire_config() non disponible (SPIP non chargé)');
        }

        $nom = backup_img_nom_fichier();
        $this->assertStringEndsWith('.zip', $nom);
    }
}
