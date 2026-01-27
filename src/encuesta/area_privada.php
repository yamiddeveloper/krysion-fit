<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Habilitar errores para depuración (quitar en producción)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once dirname(__DIR__) . '/wp-load.php';


// Verificamos si el usuario ha iniciado sesión en WordPress O en nuestra sesión personalizada
$is_logged_in = is_user_logged_in() || isset($_SESSION['usuario_id']);

if ( ! $is_logged_in ) {
    header("Location: login.php");
    exit();
}

// 1. SI ES ADMINISTRADOR -> REDIRECCIÓN ESPECIAL
if (is_user_logged_in()) {
    $current_user = wp_get_current_user();
    if (in_array('administrator', $current_user->roles)) {
        header("Location: /planes-de-entrenamiento-y-nutricion/");
        exit();
    }
}

// Obtener el email del usuario
if (is_user_logged_in()) {
    $current_user = wp_get_current_user();
    $user_email = $current_user->user_email;
} else if (isset($_SESSION['usuario_email'])) {
    $user_email = $_SESSION['usuario_email'];
} else {
    // Si no tenemos email, no podemos buscar el plan
    header("Location: login.php?error=system_error");
    exit();
}

require 'db.php';

// Buscamos los datos del plan personalizado usando el email
$stmt = $pdo->prepare("SELECT * FROM planes_personalizados WHERE email = ?");
$stmt->execute([$user_email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    // Si no hay datos en la tabla personalizada, redirigimos al login con mensaje
    // IMPORTANTE: Cerramos sesión de WP y PHP para evitar bucle de redirección 
    // si un plugin de WP (como Ultimate Member) intenta forzar el acceso al perfil.
    if (is_user_logged_in()) {
        wp_logout();
    }
    session_destroy();
    header("Location: login.php?error=no_plan");
    exit();
}

// El estado de aprobación se maneja mediante el meta de usuario de WP si existe
$is_approved = false;
if (is_user_logged_in()) {
    $approval_status = get_user_meta($current_user->ID, 'kf_user_approval', true);
    $is_approved = ($approval_status === 'approved');
} else {
    // Si solo está en sesión personalizada, podríamos buscarlo en la DB de WP o usar un valor por defecto
    $wp_user = get_user_by('email', $user_email);
    if ($wp_user) {
        $approval_status = get_user_meta($wp_user->ID, 'kf_user_approval', true);
        $is_approved = ($approval_status === 'approved');
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Krysion Fit - Mi Cuenta</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #F2600C;
            --primary-glow: rgba(242, 96, 12, 0.5);
            --bg-dark: #050505;
            --text-light: #FFFFFF;
            --text-muted: #999999;
            --card-surface: rgba(20, 20, 20, 0.6);
            --card-border: rgba(255, 255, 255, 0.08);
            --text-main: #FFFFFF;
            --text-secondary: #999999;
            --radius-lg: 32px;
            --radius-md: 20px;
            --font-main: 'Outfit', sans-serif;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: var(--font-main) !important;
            background-color: var(--bg-dark) !important;
            background-image: 
                radial-gradient(circle at 10% 20%, rgba(242, 96, 12, 0.08) 0%, transparent 40%),
                radial-gradient(circle at 90% 80%, rgba(242, 96, 12, 0.05) 0%, transparent 40%) !important;
            background-attachment: fixed !important;
            margin: 0;
            padding: 0;
            color: var(--text-main);
        }

        /* NAV MANUALLY IMITATED */
        nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 18px 30px;
            background: rgba(5, 5, 5, 0.85);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            position: sticky;
            top: 0;
            z-index: 50;
            border-bottom: 1px solid var(--card-border);
        }
        nav .logo {
            color: var(--primary);
            font-weight: 800;
            font-size: 1.4rem;
            letter-spacing: -0.02em;
            text-decoration: none;
            font-style: italic;
        }
        nav a {
            text-decoration: none;
        }
        nav ul {
            list-style: none;
            display: flex;
            gap: 32px;
            margin: 0;
            padding: 0;
        }
        nav ul li a {
            color: var(--text-light);
            font-size: 0.95rem;
            font-weight: 500;
            opacity: 0.85;
            position: relative;
            transition: 0.3s ease;
        }
        nav ul li a:hover {
            color: var(--primary);
            opacity: 1;
        }
        nav .btn-area {
            background: transparent;
            color: var(--text-light);
            padding: 10px 24px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.9rem;
            border: 1px solid rgba(255,255,255,0.2);
            transition: 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        nav .btn-area:hover,
        nav .btn-area.active {
            background: var(--primary);
            border-color: var(--primary);
            color: #fff;
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(242, 96, 12, 0.4);
        }

        .private-area-wrapper {
            min-height: calc(100vh - 200px);
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 60px 20px 100px;
            position: relative;
            z-index: 1;
            margin-bottom: 80px;
        }

        .private-area-content {
            width: 100%;
            max-width: 900px;
            margin: 0 auto;
            flex: 1;
        }

        /* HEADER */
        .private-header {
            text-align: center;
            margin-bottom: 50px;
            animation: fadeInDown 0.8s ease-out;
        }
        .private-header h1 {
            font-size: 3rem;
            font-weight: 800;
            letter-spacing: -0.03em;
            margin: 0 0 10px;
            background: linear-gradient(to right, #fff, #bbb);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .private-header p {
            font-size: 1.1rem;
            color: var(--text-secondary);
            font-weight: 400;
        }

        /* CARD ESTILIZADA */
        .profile-card {
            background: var(--card-surface);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border: 1px solid var(--card-border);
            border-radius: var(--radius-lg);
            padding: 60px;
            box-shadow: 0 24px 60px rgba(0,0,0,0.4);
            position: relative;
            overflow: visible;
            animation: fadeInUp 0.8s ease-out 0.2s both;
            margin-bottom: 40px;
        }

        /* Efecto de luz superior */
        .profile-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
        }

        /* LAYOUT INTERNO */
        .profile-grid-layout {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 60px;
            align-items: start;
        }

        /* COLUMNA IZQUIERDA (FOTO) */
        .profile-left {
            text-align: center;
        }

        .avatar-container {
            width: 220px;
            height: 220px;
            margin: 0 auto 25px;
            position: relative;
            z-index: 1;
        }
        
        .avatar-circle {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            padding: 6px;
            background: linear-gradient(135deg, rgba(255,255,255,0.1), rgba(255,255,255,0.02));
            border: 1px solid rgba(255,255,255,0.1);
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
        }

        .avatar-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
            filter: grayscale(20%) contrast(110%);
            transition: 0.4s ease;
        }
        
        .avatar-container:hover .avatar-img {
            filter: grayscale(0%) contrast(100%);
            transform: scale(1.02);
        }

        /* Badge de Estado */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(242, 96, 12, 0.15);
            color: var(--primary);
            padding: 8px 16px;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 600;
            letter-spacing: 0.05em;
            border: 1px solid rgba(242, 96, 12, 0.3);
        }
        .status-dot {
            width: 6px; 
            height: 6px; 
            background: var(--primary); 
            border-radius: 50%; 
            box-shadow: 0 0 10px var(--primary);
            animation: pulse 2s infinite;
        }

        /* COLUMNA DERECHA (DATOS) */
        .user-name {
            font-size: 2.2rem;
            font-weight: 700;
            color: #fff;
            margin-bottom: 30px;
            line-height: 1.1;
        }
        .user-name span {
            color: var(--primary);
        }

        /* GRID DE STATS REFINADO */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
            gap: 16px;
            margin-bottom: 30px;
        }

        .stat-box {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 18px;
            padding: 20px 15px;
            text-align: center;
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            position: relative;
            overflow: hidden;
        }

        .stat-box:hover {
            background: rgba(255, 255, 255, 0.06);
            border-color: rgba(255, 255, 255, 0.15);
            transform: translateY(-4px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }

        /* Icono sutil de fondo */
        .stat-box svg {
            position: absolute;
            top: -10px;
            right: -10px;
            width: 60px;
            height: 60px;
            color: rgba(255,255,255,0.02);
            transform: rotate(-15deg);
            pointer-events: none;
        }

        .stat-label {
            display: block;
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.15em;
            color: var(--text-secondary);
            margin-bottom: 8px;
            font-weight: 600;
        }

        .stat-value {
            display: block;
            font-size: 1.1rem;
            font-weight: 600;
            color: #fff;
        }

        /* CAJA DE MENSAJE MÉDICO */
        .medical-box {
            background: rgba(255, 77, 77, 0.06);
            border: 1px solid rgba(255, 77, 77, 0.2);
            border-radius: 16px;
            padding: 20px;
            display: flex;
            gap: 15px;
            align-items: start;
            margin-top: 25px;
        }
        .medical-icon {
            color: #FF4D4D;
            font-size: 1.2rem;
            background: rgba(255, 77, 77, 0.1);
            padding: 8px;
            border-radius: 10px;
        }

        /* BOTONES ACCION */
        .actions-row {
            margin-top: 40px;
            padding-top: 30px;
            border-top: 1px solid var(--card-border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .btn-logout {
            color: var(--text-secondary);
            font-size: 0.9rem;
            font-weight: 500;
            padding: 10px 20px;
            border-radius: 50px;
            transition: 0.3s;
            border: 1px solid transparent;
            text-decoration: none;
        }
        .btn-logout:hover {
            color: #FF4D4D;
            background: rgba(255, 77, 77, 0.05);
            border-color: rgba(255, 77, 77, 0.2);
        }

        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(242, 96, 12, 0.4); }
            70% { box-shadow: 0 0 0 6px rgba(242, 96, 12, 0); }
            100% { box-shadow: 0 0 0 0 rgba(242, 96, 12, 0); }
        }
        @keyframes pulse-green {
            0% { box-shadow: 0 0 0 0 rgba(40, 167, 69, 0.4); }
            70% { box-shadow: 0 0 0 10px rgba(40, 167, 69, 0); }
            100% { box-shadow: 0 0 0 0 rgba(40, 167, 69, 0); }
        }
        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* RESPONSIVE */
        @media (max-width: 900px) {
            nav { padding: 15px 20px; }
            nav ul { display: none; }
            
            .profile-grid-layout {
                grid-template-columns: 1fr;
                gap: 40px;
                text-align: center;
            }
            .profile-left {
                display: flex;
                flex-direction: column;
                align-items: center;
            }
            .user-name { 
                margin-top: 0;
                font-size: 1.8rem;
                text-align: center;
            }
            .stats-grid { 
                grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); 
            }
            .actions-row { 
                flex-direction: column; 
                gap: 20px;
                text-align: center;
            }
            .private-area-wrapper { 
                padding: 60px 15px 60px;
                margin-bottom: 40px;
            }
            .profile-card { 
                padding: 30px 20px;
                margin-bottom: 20px;
            }
            .private-header h1 {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>

<nav>
    <a href="<?php echo esc_url( home_url('/') ); ?>">
        <div class="logo">
            KRYSION FIT
        </div>
    </a>

    <ul>
        <li><a href="<?php echo esc_url( home_url('/#top') ); ?>">Inicio</a></li>
        <li><a href="<?php echo esc_url( home_url('/#coach') ); ?>">Coach</a></li>
        <li><a href="<?php echo esc_url( home_url('/#como-funciona') ); ?>">Cómo funciona</a></li>
        <li><a href="<?php echo esc_url( home_url('/#planes') ); ?>">Precios</a></li>
        <li><a href="<?php echo esc_url( home_url('/#contacto') ); ?>">Contacto</a></li>
    </ul>

    <div>
        <a href="area_privada.php" class="btn-area active">
            Mi cuenta
        </a>
    </div>
</nav>

<div class="private-area-wrapper">
    <div class="private-area-content">
        
        <div class="private-header">
            <h1>Bienvenido, <span><?php echo htmlspecialchars(explode(' ', $user['full_name'])[0]); ?></span></h1>
            <p>Tu transformación está en proceso. Aquí tienes tu perfil.</p>
        </div>

        <div class="profile-card">
            <div class="profile-grid-layout">
                
                <!-- COLUMNA IZQ: FOTO -->
                <div class="profile-left">
                    <div class="avatar-container">
                        <div class="avatar-circle">
                            <?php if(!empty($user['photo_path'])): ?>
                                <img src="<?php echo htmlspecialchars($user['photo_path']); ?>" class="avatar-img" alt="Foto Perfil">
                            <?php else: ?>
                                <div style="width:100%; height:100%; border-radius:50%; background:#1a1a1a; display:flex; align-items:center; justify-content:center; color:#555; font-weight:700;">
                                    SIN FOTO
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="status-badge" style="<?php echo !$is_approved ? 'background: rgba(255, 77, 77, 0.15); color: #FF4D4D; border-color: rgba(255, 77, 77, 0.3);' : 'background: rgba(42, 170, 85, 0.15); color: #2AAA55; border-color: rgba(42, 170, 85, 0.3);'; ?>">
                        <span class="status-dot" style="<?php echo !$is_approved ? 'background: #FF4D4D; box-shadow: 0 0 10px #FF4D4D; animation: pulse 2s infinite;' : 'background: #2AAA55; box-shadow: 0 0 10px #2AAA55; animation: pulse-green 2s infinite;'; ?>"></span>
                        <?php echo $is_approved ? 'PLAN ACTIVADO' : 'PAGO PENDIENTE'; ?>
                    </div>

                    <?php if ($is_approved): ?>
                        <div style="margin-top: 25px;">
                            <a href="ver-plan.php" class="btn-ver-plan">
                                <span class="btn-text">VER MI PLAN PERSONALIZADO</span>
                                <span class="btn-glow"></span>
                            </a>
                        </div>
                        <style>
                            .btn-ver-plan {
                                position: relative;
                                display: flex;
                                align-items: center;
                                justify-content: center;
                                gap: 10px;
                                width: 100%;
                                max-width: 240px;
                                margin: 0 auto;
                                padding: 14px 24px;
                                background: transparent;
                                color: var(--primary);
                                text-decoration: none;
                                border-radius: 50px;
                                font-weight: 700;
                                font-size: 0.8rem;
                                letter-spacing: 0.08em;
                                text-transform: uppercase;
                                transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
                                border: 1.5px solid var(--primary);
                                overflow: hidden;
                            }
                            
                            .btn-ver-plan:hover {
                                background: var(--primary);
                                color: #fff;
                                transform: translateY(-2px);
                                box-shadow: 0 8px 20px rgba(242, 96, 12, 0.3);
                            }
                            
                            .btn-ver-plan:active {
                                transform: translateY(0);
                            }
                            
                            .btn-ver-plan .btn-icon {
                                font-size: 1rem;
                            }
                            
                            .btn-glow {
                                display: none;
                            }
                            
                            @media (max-width: 900px) {
                                .btn-ver-plan { margin: 0 auto; }
                            }
                        </style>
                    <?php endif; ?>
                </div>

                <!-- COLUMNA DER: INFO -->
                <div class="profile-right">
                    <h2 class="user-name"><?php echo htmlspecialchars($user['full_name']); ?></h2>

                    <?php if (!$is_approved): ?>
                        <div style="background: rgba(255,255,255,0.03); border: 1px solid var(--card-border); border-radius: 20px; padding: 25px; margin-bottom: 30px; text-align: left;">
                            <h3 style="color: var(--primary); margin-bottom: 10px; font-size: 1.1rem;">⏳ Activación en curso</h3>
                            <p style="font-size: 0.9rem; color: var(--text-secondary); line-height: 1.5; margin-bottom: 15px;">
                                Tu cuenta está en estado <strong>No Aprobada</strong>. Para activar tu plan, recuerda enviar tu comprobante de pago por WhatsApp.
                            </p>
                            <a href="https://wa.me/51907356?text=<?php echo urlencode('Hola Krysion Fit, ya realicé mi pago. Mi correo es: ' . $user['email']); ?>" target="_blank" class="btn-logout" style="background: var(--primary); color: #fff; border-color: var(--primary); display: inline-block;">
                                Enviar Comprobante
                            </a>
                        </div>
                    <?php endif; ?>

                    <div class="stats-grid" style="<?php echo !$is_approved ? 'opacity: 0.5; pointer-events: none;' : ''; ?>">
                        <!-- Objetivo -->
                        <div class="stat-box">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                            <span class="stat-label">Objetivo</span>
                            <span class="stat-value">
                                <?php 
                                    $goals = [
                                        'lose_fat' => 'Perder Grasa',
                                        'gain_muscle' => 'Ganar Músculo',
                                        'recomp' => 'Recomposición'
                                    ];
                                    echo $goals[$user['goal']] ?? 'Desconocido'; 
                                ?>
                            </span>
                        </div>

                        <!-- Peso -->
                        <div class="stat-box">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3" /></svg>
                            <span class="stat-label">Peso Actual</span>
                            <span class="stat-value"><?php echo $user['weight']; ?> kg</span>
                        </div>

                        <!-- Altura -->
                        <div class="stat-box">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 14l-7 7m0 0l-7-7m7 7V3" /></svg>
                            <span class="stat-label">Altura</span>
                            <span class="stat-value"><?php echo $user['height']; ?> cm</span>
                        </div>

                        <!-- Edad -->
                        <div class="stat-box">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                            <span class="stat-label">Edad</span>
                            <span class="stat-value"><?php echo $user['age']; ?> años</span>
                        </div>

                        <!-- Nivel Actividad -->
                        <div class="stat-box">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                            <span class="stat-label">Actividad</span>
                            <span class="stat-value">
                                <?php 
                                    $levels = ['sedentary' => 'Baja', 'moderate' => 'Media', 'high' => 'Alta'];
                                    echo $levels[$user['activity_level']] ?? 'Media'; 
                                ?>
                            </span>
                        </div>

                         <!-- Lugar -->
                         <div class="stat-box">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" /></svg>
                            <span class="stat-label">Entreno en</span>
                            <span class="stat-value"><?php echo ($user['equipment'] == 'gym' ? 'Gimnasio' : 'Casa'); ?></span>
                        </div>
                    </div>

                    <!-- Lesiones (Solo si existen) -->
                    <?php 
                        $injuries_clean = trim(strtolower($user['injuries'] ?? ''));
                        $ignore_list = ['', 'no', 'ninguna', 'ninguno', 'n/a', 'nop', 'none', '0', 'no tengo'];
                        
                        if (!in_array($injuries_clean, $ignore_list)): 
                    ?>
                    <div class="medical-box">
                        <div class="medical-icon">⚠</div>
                        <div>
                            <strong style="color:#FF4D4D; display:block; margin-bottom:4px; font-size:0.9rem;">OBSERVACIÓN MÉDICA</strong>
                            <p style="margin:0; font-size:0.95rem; color:var(--text-secondary); line-height:1.5;">
                                <?php echo htmlspecialchars($user['injuries']); ?>
                            </p>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="actions-row">
                        <div style="font-size:0.85rem; color:#666;">
                            Krysion Fit ID: <span style="font-family:monospace; color:#888;">#<?php echo str_pad($user['id'], 6, '0', STR_PAD_LEFT); ?></span>
                        </div>
                        <a href="logout.php" class="btn-logout">Cerrar Sesión</a>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
