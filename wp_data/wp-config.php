<?php
/** Configuración básica de WordPress para Yamid Dev */

define( 'DB_NAME', 'wordpress' );
define( 'DB_USER', 'wp_user' );
define( 'DB_PASSWORD', 'Tumaiwaraka100' );
define( 'DB_HOST', 'db' );
define( 'DB_CHARSET', 'utf8mb4' );
define( 'DB_COLLATE', '' );

/** Llaves de Seguridad (Valores fijos para evitar errores de función) */
define( 'AUTH_KEY',         'd928ffae603af0be02044642038123c94ef0916e' );
define( 'SECURE_AUTH_KEY',  '6c901ed40809faed01895d7379292525af89ddfa' );
define( 'LOGGED_IN_KEY',    'f96f09c8796ad0ecc130aad911e70230e476fc42' );
define( 'NONCE_KEY',        '0202d5171289a74b55cffeec46e15832de646e3a' );
define( 'AUTH_SALT',        '209c514ec3a896ceecaf2304a25551af4970a3b3' );
define( 'SECURE_AUTH_SALT', 'ea30e5d41ee9fa778c01867a1836dce690376299' );
define( 'LOGGED_IN_SALT',   '93ea010e8b4f09277c158ccf80939bb074cf7dcb' );
define( 'NONCE_SALT',       '6e05611eed560002244c52058c4015d44f2cfc5a' );

$table_prefix = 'wp_';

/** Modo Debug Activo para ver qué falta */
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_DISPLAY', true );
define( 'WP_DEBUG_LOG', true );
@ini_set( 'display_errors', 1 );

define('WP_HOME', 'https://krysionfit.com');
define('WP_SITEURL', 'https://krysionfit.com');

// Esto ayuda a que el SSL se reconozca detrás del proxy
if (strpos($_SERVER['HTTP_X_FORWARDED_PROTO'], 'https') !== false) {
    $_SERVER['HTTPS'] = 'on';
}

/** Detección de HTTPS para el túnel de Cloudflare */
if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strpos($_SERVER['HTTP_X_FORWARDED_PROTO'], 'https') !== false) {
    $_SERVER['HTTPS'] = 'on';
}

$kf_proto = (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) ? $_SERVER['HTTP_X_FORWARDED_PROTO'] : 'http');
$kf_host  = (!empty($_SERVER['HTTP_X_FORWARDED_HOST'])  ? $_SERVER['HTTP_X_FORWARDED_HOST']  : $_SERVER['HTTP_HOST']);

if (strpos($kf_host, 'localhost') !== false) {
    $kf_proto = 'http';
}

//define('WP_HOME', $kf_proto . '://' . $kf_host);
//define('WP_SITEURL', $kf_proto . '://' . $kf_host);

/** Ruta absoluta al directorio de WordPress */
if ( ! defined( 'ABSPATH' ) ) {
    define( 'ABSPATH', __DIR__ . '/' );
}

/** Carga de archivos de WordPress */
require_once ABSPATH . 'wp-settings.php';