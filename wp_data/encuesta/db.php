<?php
$host = 'db';              
$dbname = 'wordpress';     
$user = 'wp_user';         
$pass = 'Tumaiwaraka100';         

try {
    // Agregamos el puerto 3306 por seguridad, aunque es el default
    $pdo = new PDO("mysql:host=$host;port=3306;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // Si da error, nos dirá exactamente qué falló
    die("Error de conexión: " . $e->getMessage());
}
?>