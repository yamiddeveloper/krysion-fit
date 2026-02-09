<?php
// Test script para verificar el logout
session_start();

echo "<h1>Test de Logout</h1>";

// Mostrar estado actual
echo "<h2>Estado ANTES del logout:</h2>";
echo "is_user_logged_in(): " . (function_exists('is_user_logged_in') && is_user_logged_in() ? 'SÍ' : 'NO') . "<br>";
echo "Session usuario_id: " . (isset($_SESSION['usuario_id']) ? $_SESSION['usuario_id'] : 'NO') . "<br>";
echo "Session usuario_email: " . (isset($_SESSION['usuario_email']) ? $_SESSION['usuario_email'] : 'NO') . "<br>";

if (function_exists('wp_get_current_user')) {
    $current_user = wp_get_current_user();
    echo "WP User ID: " . (isset($current_user->ID) ? $current_user->ID : 'NO') . "<br>";
    echo "WP User Email: " . (isset($current_user->user_email) ? $current_user->user_email : 'NO') . "<br>";
}

// Mostrar cookies actuales
echo "<h2>Cookies actuales:</h2>";
foreach ($_COOKIE as $name => $value) {
    if (strpos($name, 'wordpress') !== false || strpos($name, 'PHPSESSID') !== false) {
        echo "$name: $value<br>";
    }
}

echo "<br><a href='logout.php'>Hacer logout ahora</a>";
echo "<br><a href='area_privada.php'>Ir a área privada</a>";
?>
