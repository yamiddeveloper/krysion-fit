<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title><?php wp_title('|', true, 'right'); bloginfo('name'); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
          --primary:#F2600C;
          --primary-dark:#F2490C;
          --primary-darkest:#731F0D;
          --bg-dark:#050505;
          --bg-section:#0D0D0D;
          --text-light:#FFFFFF;
          --text-muted:#999999;
          --card-border:rgba(255, 255, 255, 0.08);
          --radius:22px;
          --shadow-soft:0 18px 45px rgba(0,0,0,0.65);
          --font-main: 'Outfit', sans-serif;
        }

        body, nav, button, input, select, textarea {
          font-family: var(--font-main) !important;
        }

        /* NAV */
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
        
        /* RESPONSIVE */
        @media (max-width: 900px){
          nav { padding: 15px 20px; }
          nav ul { display: none; }
        }
    </style>

    <?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>

<nav>
    <a href="<?php echo esc_url( home_url('/') ); ?>">
        <div class="logo" style="font-style: italic;">
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

    <?php 
        $current_uri = $_SERVER['REQUEST_URI'];
        $is_panel = (strpos($current_uri, '/planes-de-entrenamiento-y-nutricion/') !== false);
        $is_mi_cuenta = (strpos($current_uri, '/encuesta/area_privada.php') !== false);
    ?>
    <div>
    <?php if ( is_user_logged_in() ) : ?>
        <?php if ( current_user_can('administrator') ) : ?>
            <a href="<?php echo esc_url( home_url('/planes-de-entrenamiento-y-nutricion/') ); ?>" class="btn-area <?php echo $is_panel ? 'active' : ''; ?>">
                Panel
            </a>
            <a href="<?php echo esc_url( admin_url() ); ?>" target="_blank" class="btn-area" style="margin-left: 10px;">
                wp-admin
            </a>
        <?php else : ?>
            <a href="<?php echo esc_url( home_url('/encuesta/area_privada.php') ); ?>" class="btn-area <?php echo $is_mi_cuenta ? 'active' : ''; ?>">
                Mi cuenta
            </a>
        <?php endif; ?>
    <?php else : ?>
        <a href="<?php echo esc_url( home_url('/encuesta/login.php') ); ?>" class="btn-area">
            Área privada
        </a>
    <?php endif; ?>
    </div>
</nav>