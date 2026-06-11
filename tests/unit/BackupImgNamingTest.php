<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2) . '/inc/backup_img.php';
require_once dirname(__DIR__, 2) . '/backup_img_fonctions.php';

final class BackupImgNamingTest extends TestCase
{
    protected function setUp(): void
    {
        $GLOBALS['_test_config'] = [];
    }

    public function testNomFichierAvecConfigParDefaut(): void
    {
        $nom = backup_img_nom_fichier();

        $this->assertMatchesRegularExpression(
            '/^backup_img_\d{8}_\d{6}\.zip$/',
            $nom,
            'Le nom par défaut doit correspondre à backup_img_YYYYMMDD_HHMMSS.zip'
        );
    }

    public function testNomFichierAvecPrefixePersonnalise(): void
    {
        $GLOBALS['_test_config']['backup_img/prefixe'] = 'mon_site';

        $nom = backup_img_nom_fichier();

        $this->assertStringStartsWith('mon_site_', $nom);
        $this->assertStringEndsWith('.zip', $nom);
    }

    public function testNomFichierAvecFormatDatePersonnalise(): void
    {
        $GLOBALS['_test_config']['backup_img/prefixe']     = 'backup';
        $GLOBALS['_test_config']['backup_img/format_date'] = 'Y';

        $nom = backup_img_nom_fichier();

        $annee = date('Y');
        $this->assertSame('backup_' . $annee . '.zip', $nom);
    }

    public function testNomFichierExtensionZip(): void
    {
        $nom = backup_img_nom_fichier();
        $this->assertStringEndsWith('.zip', $nom);
    }
}
