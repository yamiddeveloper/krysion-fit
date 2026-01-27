<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once dirname(__DIR__) . '/wp-load.php';
require 'db.php';

// Si ya está logueado, mandarlo directo al área privada
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
        // 1. SI ES ADMINISTRADOR -> REDIRECCIÓN ESPECIAL
        if (in_array('administrator', $user_wp->roles)) {
            wp_set_auth_cookie($user_wp->ID); // Asegurar cookie
            header("Location: /planes-de-entrenamiento-y-nutricion/");
            exit();
        }

        // 2. Buscar datos del plan en la BD personalizada usando el email de WP
        // (Por si el email ingresado fue un username de WP diferente al email)
        $user_email_wp = $user_wp->user_email;
        $stmt = $pdo->prepare("SELECT * FROM planes_personalizados WHERE email = :email");
        $stmt->execute([':email' => $user_email_wp]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usuario) {
            // Login correcto
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['usuario_nombre'] = $usuario['full_name'];
            $_SESSION['usuario_email'] = $usuario['email'];
            
            // Asegurar que esté logueado en WP
            wp_set_current_user($user_wp->ID);
            wp_set_auth_cookie($user_wp->ID);

            header("Location: area_privada.php");
            exit();
        } else {
            $error = "Tu usuario de WordPress es válido pero no tienes un plan activo configurado.";
            // Si no tiene plan, no lo dejamos logueado en WP para mantener consistencia
            wp_logout();
        }
    } else {
        $error = "Usuario o contraseña de WordPress incorrectos.";
    }
}

get_header(); ?>

<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

<style>
  :root {
      --primary: #F2600C;
      --primary-glow: rgba(242, 96, 12, 0.5);
      --bg-dark: #050505;
      --card-surface: rgba(20, 20, 20, 0.6);
      --card-border: rgba(255, 255, 255, 0.08);
      --text-main: #FFFFFF;
      --text-secondary: #999999;
      --radius-lg: 32px;
      --font-main: 'Outfit', sans-serif;
      --error-color: #FF4D4D;
  }
  
  /* FORZAR ESTRUCTURA DE PÁGINA CORRECTA (ESTILO WP) */
  html, body {
      height: auto !important;
      min-height: 100vh !important;
  }
  
  body {
      display: flex !important;
      flex-direction: column !important;
      background-color: var(--bg-dark) !important;
      background-image: 
            radial-gradient(circle at 10% 20%, rgba(242, 96, 12, 0.08) 0%, transparent 40%),
            radial-gradient(circle at 90% 80%, rgba(242, 96, 12, 0.05) 0%, transparent 40%) !important;
      background-attachment: fixed !important;
      margin: 0 !important;
      font-family: var(--font-main) !important;
      color: var(--text-main) !important;
  }
  
  /* Asegurar que el contenido principal empuje el footer */
  main,
  #main,
  .site-content,
  #content,
  .login-wrapper {
      flex: 1 0 auto !important;
      display: flex;
      align-items: center;
      justify-content: center;
      width: 100%;
  }

  /* FORZAR FOOTER AL FINAL */
  footer,
  .site-footer,
  #colophon {
      margin-top: auto !important;
  }

  .login-card-container {
      width: 100%;
      max-width: 450px;
      margin: 60px 20px;
      background: var(--card-surface);
      backdrop-filter: blur(24px);
      -webkit-backdrop-filter: blur(24px);
      border-radius: var(--radius-lg);
      border: 1px solid var(--card-border);
      box-shadow: 0 24px 60px rgba(0,0,0,0.4);
      padding: 50px 40px;
      text-align: center;
      position: relative;
      z-index: 10;
      animation: fadeInUp 0.8s ease-out;
  }

  /* Efecto de luz superior */
  .login-card-container::before {
      content: '';
      position: absolute;
      top: 0; left: 0; right: 0;
      height: 1px;
      background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
  }

  .login-card-container h2 {
      font-size: 2.5rem;
      font-weight: 800;
      margin-bottom: 10px;
      letter-spacing: -0.02em;
      background: linear-gradient(to right, #fff, #bbb);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
  }

  .login-card-container p.subtitle {
      color: var(--text-secondary);
      margin-bottom: 35px;
      font-size: 1rem;
      font-weight: 400;
  }

  .login-field {
      margin-bottom: 22px;
      text-align: left;
  }

  .login-card-container input {
      width: 100%;
      padding: 18px 25px;
      background: rgba(255, 255, 255, 0.03);
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 50px;
      color: #fff;
      font-family: inherit;
      font-size: 16px;
      transition: all 0.3s ease;
      text-align: center;
  }

  .login-card-container input:focus {
      outline: none;
      border-color: var(--primary);
      background: rgba(242, 96, 12, 0.05);
      box-shadow: 0 0 20px rgba(242, 96, 12, 0.1);
  }

  .btn-login {
      width: 100%;
      padding: 16px 40px;
      border-radius: 50px;
      font-weight: 700;
      cursor: pointer;
      border: none;
      font-size: 15px;
      transition: all 0.3s;
      text-transform: uppercase;
      letter-spacing: 0.1em;
      background: var(--primary);
      color: #fff;
      box-shadow: 0 8px 25px rgba(242, 96, 12, 0.3);
      margin-top: 10px;
  }

  .btn-login:hover {
      background: #ff7020; /* Ligeramente más claro que el primary */
      transform: translateY(-2px);
      box-shadow: 0 12px 35px rgba(242, 96, 12, 0.5);
  }

  .login-error {
      background: rgba(255, 77, 77, 0.1);
      color: var(--error-color);
      padding: 14px;
      border-radius: 16px;
      font-size: 14px;
      margin-bottom: 25px;
      border: 1px solid rgba(255, 77, 77, 0.2);
  }

  .login-links {
      margin-top: 30px;
  }

  .login-links a {
      display: inline-block;
      color: var(--text-secondary);
      text-decoration: none;
      font-size: 14px;
      transition: 0.3s;
  }

  .login-links a:hover {
      color: var(--primary);
  }

  @keyframes fadeInUp {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
  }

  @media (max-width: 480px) {
      .login-card-container { 
          padding: 40px 25px; 
          border-radius: 24px;
          margin: 40px 15px;
      }
      .login-card-container h2 { font-size: 2rem; }
  }
</style>

<div class="login-wrapper">
    <div class="login-card-container">
        <h2>Área Privada</h2>
        <p class="subtitle">Ingresa tus credenciales para acceder a tu plan personalizado</p>

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
            <button type="submit" class="btn-login">Iniciar Sesión</button>
        </form>
        
        <div class="login-links">
            <a href="<?php echo home_url(); ?>">← Volver a la página principal</a>
        </div>
    </div>
</div>

<?php get_footer(); ?>