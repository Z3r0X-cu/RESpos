<?php
// RESpos - configuración central. Si lo instalas dentro de otra carpeta, cambia APP_BASE_URL si deseas fijarla manualmente.
const APP_NAME = 'RESpos';
const APP_CREATOR = 'Z3r0X';
// URL oficial / repositorio del proyecto. Marcada para poder cambiarla posteriormente.
const PROJECT_GITHUB_URL = 'https://github.com/Z3r0X-cu/RESpos';
// Dejar vacío para autodetectar /RESpos, /, etc. Ejemplo manual: '/RESpos'
const APP_BASE_URL = '';
const DEFAULT_TIMEZONE = 'America/Havana';
date_default_timezone_set(DEFAULT_TIMEZONE);

function app_base_url(): string {
    if (APP_BASE_URL !== '') return rtrim(APP_BASE_URL, '/');
    $root = str_replace('\\', '/', realpath(__DIR__ . '/..'));
    $doc = isset($_SERVER['DOCUMENT_ROOT']) ? str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'])) : '';
    if ($doc && $root && strpos($root, $doc) === 0) {
        $rel = str_replace($doc, '', $root);
        return $rel === '/' ? '' : rtrim($rel, '/');
    }
    return '/RESpos';
}
function url(string $path=''): string { return app_base_url() . '/' . ltrim($path, '/'); }
?>
