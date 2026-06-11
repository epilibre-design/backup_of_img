<?php
if (!defined('_ECRIRE_INC_VERSION')) {
    return;
}

function backup_img_autoriser(): void {}

function autoriser_backupimgcreer_dist(string $faire, string $type, $id, array $qui, array $opt): bool
{
    return $qui['webmestre'] === 'oui';
}

function autoriser_backupimgsupprimer_dist(string $faire, string $type, $id, array $qui, array $opt): bool
{
    return $qui['webmestre'] === 'oui';
}

function autoriser_backupimgtelecharger_dist(string $faire, string $type, $id, array $qui, array $opt): bool
{
    return $qui['webmestre'] === 'oui';
}

function autoriser_configurerbackupimg_dist(string $faire, string $type, $id, array $qui, array $opt): bool
{
    return $qui['webmestre'] === 'oui';
}
