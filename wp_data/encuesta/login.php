<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once dirname(__DIR__) . '/wp-load.php';
require 'db.php';

// 1. VERIFICACIÓN INICIAL: Si ya hay sesión, redirigir según rol
if (is_user_logged_in() || isset($_SESSION['usuario_id'])) {
    if (is_user_logged_in()) {
        $current_user = wp_get_current_user();
        if (in_array('administrator', $current_user->roles)) {
            header("Location: /planes-de-entrenamiento-y-nutricion/");
            exit();
        }
    }
    header("Location: area_privada.php");
    exit();
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];

    // A. Autenticar con WordPress
    $creds = [
        'user_login'    => $email,
        'user_password' => $password,
        'remember'      => true
    ];
    
    $user_wp = wp_signon($creds, false);

    if (!is_wp_error($user_wp)) {
        // Establecer cookies de WP inmediatamente para evitar deslogueos
        wp_set_current_user($user_wp->ID);
        wp_set_auth_cookie($user_wp->ID);

        // B. LÓGICA DE REDIRECCIÓN POR ROL
        
        // 1. PRIORIDAD: ADMINISTRADOR
        if (in_array('administrator', $user_wp->roles)) {
            header("Location: /planes-de-entrenamiento-y-nutricion/");
            exit();
        }

        // 2. SI NO ES ADMIN, BUSCAR EN TABLA PERSONALIZADA
        $user_email_wp = $user_wp->user_email;
        $stmt = $pdo->prepare("SELECT * FROM planes_personalizados WHERE email = :email");
        $stmt->execute([':email' => $user_email_wp]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usuario) {
            // Guardar datos en sesión para el Área Privada
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['usuario_nombre'] = $usuario['full_name'];
            $_SESSION['usuario_email'] = $usuario['email'];

            header("Location: area_privada.php");
            exit();
        } else {
            // Si no tiene plan activo, cerramos sesión de WP para evitar inconsistencias
            wp_logout();
            session_destroy();
            $error = "Tu cuenta de WordPress es válida, pero no tienes un plan configurado en el sistema de Freddy.";
        }
    } else {
        $error = "Usuario o contraseña incorrectos.";
    }
}

get_header(); ?>

<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

<style>
  :root {
      --primary: #F2600C;
      --bg-dark: #050505;
      --card-surface: rgba(20, 20, 20, 0.6);
      --card-border: rgba(255, 255, 255, 0.08);
      --text-main: #FFFFFF;
      --text-secondary: #999999;
      --radius-lg: 32px;
      --font-main: 'Outfit', sans-serif;
      --error-color: #FF4D4D;
  }
  
  body {
      background-color: var(--bg-dark) !important;
      background-image: 
            radial-gradient(circle at 10% 20%, rgba(242, 96, 12, 0.08) 0%, transparent 40%),
            radial-gradient(circle at 90% 80%, rgba(242, 96, 12, 0.05) 0%, transparent 40%) !important;
      font-family: var(--font-main) !important;
      color: var(--text-main) !important;
  }
  
  .login-wrapper {
      min-height: 80vh;
      display: flex;
      align-items: center;
      justify-content: center;
      width: 100%;
  }

  .login-card-container {
      width: 100%;
      max-width: 450px;
      background: var(--card-surface);
      backdrop-filter: blur(24px);
      border-radius: var(--radius-lg);
      border: 1px solid var(--card-border);
      padding: 50px 40px;
      text-align: center;
      animation: fadeInUp 0.8s ease-out;
  }

  .login-card-container h2 {
      font-size: 2.5rem;
      font-weight: 800;
      margin-bottom: 10px;
      background: linear-gradient(to right, #fff, #bbb);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
  }

  .subtitle { color: var(--text-secondary); margin-bottom: 35px; }

  .login-field { margin-bottom: 22px; }

  .login-card-container input {
      width: 100%;
      padding: 18px 25px;
      background: rgba(255, 255, 255, 0.03);
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 50px;
      color: #fff;
      text-align: center;
  }

  .btn-login {
      width: 100%;
      padding: 16px;
      border-radius: 50px;
      font-weight: 700;
      background: var(--primary);
      color: #fff;
      border: none;
      cursor: pointer;
      text-transform: uppercase;
      transition: 0.3s;
  }

  .btn-login:hover { transform: translateY(-2px); box-shadow: 0 10px 20px rgba(242, 96, 12, 0.3); }

  .login-error {
      background: rgba(255, 77, 77, 0.1);
      color: var(--error-color);
      padding: 14px;
      border-radius: 16px;
      margin-bottom: 25px;
      border: 1px solid rgba(255, 77, 77, 0.2);
  }

  @keyframes fadeInUp {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
  }
</style>

<div class="login-wrapper">
    <div class="login-card-container">
        <h2>Área Privada</h2>
        <p class="subtitle">Accede a tu plan de recomposición corporal</p>

        <?php if($error): ?>
            <div class="login-error">
                <strong>⚠️ Error:</strong> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="login-field">
                <input type="email" name="email" placeholder="Correo Electrónico" required>
            </div>
            <div class="login-field">
                <input type="password" name="password" placeholder="Contraseña" required>
            </div>
            <button type="submit" class="btn-login">Entrar al Sistema</button>
        </form>
        
        <div style="margin-top: 30px;">
            <a href="<?php echo home_url(); ?>" style="color: var(--text-secondary); text-decoration: none; font-size: 14px;">← Volver al inicio</a>
        </div>
    </div>
</div>

<?php get_footer(); ?>