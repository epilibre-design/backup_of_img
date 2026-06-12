<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class BackupImgConfigTest extends TestCase
{
    protected function setUp(): void
    {
        include_spip('inc/meta');
    }

    public function testConfigInitialiseeApresActivation(): void
    {
        $prefixe = lire_config('backup_img/prefixe');
        $this->assertNotNull($prefixe, 'La config backup_img/prefixe doit être initialisée');
        $this->assertSame('backup_img', $prefixe);
    }

    public function testConfigMaxSauvegardes(): void
    {
        $max = lire_config('backup_img/max_sauvegardes');
        $this->assertSame('10', $max);
    }

    public function testConfigMaxEspaceMo(): void
    {
        $max = lire_config('backup_img/max_espace_mo');
        $this->assertSame('500', $max);
    }

    public function testConfigFtpDefaut(): void
    {
        $this->assertSame('0',  lire_config('backup_img/ftp_actif'));
        $this->assertSame('21', lire_config('backup_img/ftp_port'));
        $this->assertSame('backup_img/', lire_config('backup_img/ftp_dossier'));
    }

    public function testEcrireEtLireConfig(): void
    {
        ecrire_config('backup_img/prefixe', 'test_prefix');
        $this->assertSame('test_prefix', lire_config('backup_img/prefixe'));

        // Restaurer
        ecrire_config('backup_img/prefixe', 'backup_img');
    }
}
