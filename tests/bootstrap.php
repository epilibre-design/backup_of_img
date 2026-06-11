<?php
declare(strict_types=1);

if (!defined('_ECRIRE_INC_VERSION')) {
    define('_ECRIRE_INC_VERSION', 'test');
}

if (!defined('_DIR_RACINE')) {
    define('_DIR_RACINE', sys_get_temp_dir() . '/spip_test_racine/');
}

if (!defined('_DIR_IMG')) {
    define('_DIR_IMG', sys_get_temp_dir() . '/spip_test_img/');
}

if (!defined('_DIR_TMP')) {
    define('_DIR_TMP', sys_get_temp_dir() . '/spip_test_tmp/');
}

if (!defined('_LOG_HS'))             { define('_LOG_HS',             0); }
if (!defined('_LOG_ALERTE_ROUGE'))   { define('_LOG_ALERTE_ROUGE',   1); }
if (!defined('_LOG_CRITIQUE'))       { define('_LOG_CRITIQUE',       2); }
if (!defined('_LOG_ERREUR'))         { define('_LOG_ERREUR',         3); }
if (!defined('_LOG_AVERTISSEMENT'))  { define('_LOG_AVERTISSEMENT',  4); }
if (!defined('_LOG_INFO_IMPORTANTE')){ define('_LOG_INFO_IMPORTANTE',5); }
if (!defined('_LOG_INFO'))           { define('_LOG_INFO',           6); }
if (!defined('_LOG_DEBUG'))          { define('_LOG_DEBUG',          7); }

if (!function_exists('include_spip')) {
    function include_spip(string $path): bool { return true; }
}

if (!function_exists('_request')) {
    function _request(string $name): mixed {
        return $GLOBALS['_test_request'][$name] ?? null;
    }
}

if (!function_exists('_T')) {
    function _T(string $key, array $args = []): string { return $key; }
}

if (!function_exists('lire_config')) {
    function lire_config(string $key, mixed $default = null): mixed {
        return $GLOBALS['_test_config'][$key] ?? $default;
    }
}

if (!function_exists('spip_log')) {
    function spip_log(mixed $message, mixed $name = null): void {}
}

if (!function_exists('charger_fonction')) {
    function charger_fonction(string $nom, string $type = ''): callable {
        return function() {};
    }
}

if (!function_exists('generer_url_ecrire')) {
    function generer_url_ecrire(string $exec, string $params = ''): string {
        return '?exec=' . $exec . ($params ? '&' . $params : '');
    }
}
