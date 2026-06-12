<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2) . '/inc/backup_img.php';
require_once dirname(__DIR__, 2) . '/backup_img_fonctions.php';

final class BackupImgNomValideTest extends TestCase
{
    // --- noms acceptés ---

    public function testNomZipValide(): void
    {
        $this->assertTrue(backup_img_nom_valide('backup_img_20260612_120000.zip'));
    }

    public function testNomTarValide(): void
    {
        $this->assertTrue(backup_img_nom_valide('backup_img_20260612_120000.tar'));
    }

    public function testExtensionMajusculeAcceptee(): void
    {
        $this->assertTrue(backup_img_nom_valide('backup_img_20260612_120000.ZIP'));
        $this->assertTrue(backup_img_nom_valide('backup_img_20260612_120000.TAR'));
    }

    // --- noms refusés ---

    public function testNomVideRefuse(): void
    {
        $this->assertFalse(backup_img_nom_valide(''));
    }

    public function testExtensionInconnueRefusee(): void
    {
        $this->assertFalse(backup_img_nom_valide('backup_img_20260612_120000.gz'));
        $this->assertFalse(backup_img_nom_valide('backup_img_20260612_120000.php'));
        $this->assertFalse(backup_img_nom_valide('backup_img_20260612_120000'));
    }

    public function testTraverseeRepertoireRefusee(): void
    {
        $this->assertFalse(backup_img_nom_valide('../../../etc/passwd.zip'));
        $this->assertFalse(backup_img_nom_valide('tmp/backup_img/archive.zip'));
        $this->assertFalse(backup_img_nom_valide('/absolute/path/archive.tar'));
    }

    public function testSeparateurWindowsRefuse(): void
    {
        $this->assertFalse(backup_img_nom_valide('..\\..\\evil.zip'));
    }

    public function testDoubleExtensionPhpRefusee(): void
    {
        $this->assertFalse(backup_img_nom_valide('archive.zip.php'));
        $this->assertFalse(backup_img_nom_valide('archive.tar.php'));
    }
}
