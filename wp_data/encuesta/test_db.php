<?php
// Test database connection
$host = 'db';              
$dbname = 'wordpress';     
$user = 'wp_user';         
$pass = 'wp_pass';

try {
    $pdo = new PDO("mysql:host=$host;port=3306;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Database connection successful!\n";
    
    // Test query
    $stmt = $pdo->prepare("SELECT email, goal, activity_level FROM planes_personalizados WHERE email = ? LIMIT 1");
    $stmt->execute(['pruebas@gmail.com']);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "Found user: " . print_r($user, true);
    } else {
        echo "User not found\n";
    }
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
}
?>
