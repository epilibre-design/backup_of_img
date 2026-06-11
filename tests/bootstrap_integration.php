<?php
declare(strict_types=1);

$spipRoot = dirname(__DIR__) . '/vendor/spip/spip';

if (!defined('_SPIP_TEST_INC'))   { define('_SPIP_TEST_INC',   $spipRoot); }
if (!defined('_SPIP_TEST_CHDIR')) { define('_SPIP_TEST_CHDIR', $spipRoot); }

putenv('APP_ENV=test');
chdir($spipRoot);

if (is_file($spipRoot . '/vendor/autoload.php')) {
    require_once $spipRoot . '/vendor/autoload.php';
}
require_once $spipRoot . '/ecrire/inc_version.php';

include_spip('inc/plugin');
_chemin(dirname(__DIR__));
actualise_plugins_actifs();

// actualise_plugins_actifs() ne déclenche pas plugin_installes_meta(),
// donc l'upgrade du plugin n'est jamais appelée automatiquement.
// On charge les metas depuis la DB puis on lance l'upgrade si nécessaire.
include_spip('inc/meta');
lire_metas();

$nom_meta_version = 'backup_img_base_version';
if (
    !isset($GLOBALS['meta'][$nom_meta_version])
    || !spip_version_compare($GLOBALS['meta'][$nom_meta_version], '1.0.0', '>=')
) {
    include_spip('base/upgrade');
    include_once dirname(__DIR__) . '/backup_img_administrations.php';
    ob_start();
    backup_img_upgrade($nom_meta_version, '1.0.0');
    ob_end_clean();
    lire_metas();
}
