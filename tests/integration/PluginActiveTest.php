<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class PluginActiveTest extends TestCase
{
    public function testSpipEstBootstrappe(): void
    {
        $this->assertTrue(defined('_SPIP_TEST_INC'), 'Bootstrap SPIP non chargé');
        $this->assertTrue(defined('_ECRIRE_INC_VERSION'), 'SPIP core non initialisé');
    }

    public function testPluginEstActif(): void
    {
        include_spip('inc/plugin');
        $plugins = liste_plugin_actifs();
        $prefixes = array_map('strtolower', array_keys($plugins));
        $this->assertContains('backup_img', $prefixes, 'Le plugin backup_img n\'est pas actif');
    }

    public function testConstantesDisponibles(): void
    {
        $this->assertTrue(defined('_DIR_IMG'), '_DIR_IMG non défini');
        $this->assertTrue(defined('_DIR_TMP'), '_DIR_TMP non défini');
        $this->assertTrue(defined('_DIR_RACINE'), '_DIR_RACINE non défini');
    }
}
