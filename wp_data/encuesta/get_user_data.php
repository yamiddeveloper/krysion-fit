<?php
header('Content-Type: application/json');

// Database connection
$host = 'db';              
$dbname = 'wordpress';     
$user = 'wp_user';         
$pass = 'wp_pass';

try {
    $pdo = new PDO("mysql:host=$host;port=3306;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

if (isset($_GET['email']) && !empty($_GET['email'])) {
    $email = filter_var($_GET['email'], FILTER_SANITIZE_EMAIL);
    
    try {
        $stmt = $pdo->prepare("SELECT goal, activity_level FROM planes_personalizados WHERE email = :email ORDER BY created_at DESC LIMIT 1");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            echo json_encode([
                'success' => true,
                'goal' => $user['goal'],
                'activity_level' => $user['activity_level']
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'User not found']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Query failed']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Email parameter missing']);
}
?>
