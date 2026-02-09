<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once dirname(__DIR__) . '/wp-load.php';

// Verificamos si el usuario ha iniciado sesión
$is_logged_in = is_user_logged_in() || isset($_SESSION['usuario_id']);

if (!$is_logged_in) {
    header("Location: login.php");
    exit();
}

// Obtener el ID de usuario de WP
$wp_user_id = 0;
if (is_user_logged_in()) {
    $current_user = wp_get_current_user();
    $wp_user_id = $current_user->ID;
} else if (isset($_SESSION['usuario_email'])) {
    $wp_u = get_user_by('email', $_SESSION['usuario_email']);
    if ($wp_u) $wp_user_id = $wp_u->ID;
}

// Buscar el plan del atleta
$plan_post = null;
if ($wp_user_id) {
    $args = [
        'post_type' => 'mi-plan',
        'meta_query' => [
            [
                'key' => 'cliente',
                'value' => $wp_user_id,
                'compare' => '='
            ]
        ],
        'posts_per_page' => 1
    ];
    $query = new WP_Query($args);
    if ($query->have_posts()) {
        $plan_post = $query->posts[0];
    }
}

get_header();
?>

<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">

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
    }

    body {
        background-color: var(--bg-dark) !important;
        color: var(--text-main) !important;
        font-family: var(--font-main) !important;
        margin: 0;
        display: flex;
        flex-direction: column;
        min-height: 100vh;
    }

    .plan-view-wrapper {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 40px 20px;
    }

    .countdown-card {
        width: 100%;
        max-width: 600px;
        background: var(--card-surface);
        backdrop-filter: blur(24px);
        -webkit-backdrop-filter: blur(24px);
        border: 1px solid var(--card-border);
        border-radius: var(--radius-lg);
        padding: 60px 40px;
        text-align: center;
        box-shadow: 0 24px 60px rgba(0,0,0,0.4);
        position: relative;
        animation: fadeInUp 0.8s ease-out;
    }

    .status-title {
        font-size: 2.2rem;
        font-weight: 800;
        margin-bottom: 20px;
        background: linear-gradient(to right, #fff, #bbb);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    .status-msg {
        font-size: 1.1rem;
        color: var(--text-secondary);
        line-height: 1.6;
        margin-bottom: 40px;
    }

    .timer-container {
        display: flex;
        justify-content: center;
        gap: 20px;
        margin-bottom: 40px;
    }

    .timer-box {
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(255, 255, 255, 0.08);
        padding: 20px;
        border-radius: 20px;
        min-width: 100px;
    }

    .timer-val {
        display: block;
        font-size: 2.5rem;
        font-weight: 800;
        color: var(--primary);
        line-height: 1;
    }

    .timer-label {
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        color: var(--text-secondary);
        margin-top: 8px;
    }

    .btn-action {
        display: inline-block;
        padding: 18px 45px;
        background: var(--primary);
        color: #fff;
        text-decoration: none;
        border-radius: 50px;
        font-weight: 800;
        transition: 0.3s;
        box-shadow: 0 10px 25px rgba(242, 96, 12, 0.3);
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    .btn-action:hover {
        transform: translateY(-3px);
        box-shadow: 0 15px 35px rgba(242, 96, 12, 0.5);
        background: #ff7020;
    }

    .loader-icon {
        width: 80px;
        height: 80px;
        margin: 0 auto 30px;
        color: var(--primary);
    }
</style>

<div class="plan-view-wrapper">
    <div class="countdown-card">
        
        <?php if (!$plan_post): ?>
            <!-- CASO 1: EL ENTRENADOR AÚN NO HA CREADO EL PLAN -->
            <div class="loader-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 20h9"></path>
                    <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path>
                </svg>
            </div>
            <h2 class="status-title">Preparando tu Estrategia</h2>
            <p class="status-msg">
                ¡Tu entrenador ya está trabajando en tu perfil! <br>
                Estamos diseñando tu plan de entrenamiento y nutrición a medida. <br>
                <strong>Vuelve pronto, te notificaremos cuando la estructura esté lista.</strong>
            </p>
        <?php else: 
            $creation_time = get_post_time('U', true, $plan_post->ID);
            $target_time = $creation_time + (24 * 3600);
            $now = time();
            $remaining = $target_time - $now;
            
            // BYPASS MANUAL
            $manual_bypass = (get_post_meta($plan_post->ID, 'kf_countdown_bypass', true) === 'yes');
            if ($manual_bypass) $remaining = 0;
        ?>
            <!-- CASO 2: EL PLAN ESTÁ CREADO -->
            <?php if ($remaining > 0): ?>
                <!-- CRONÓMETRO DE 24 HORAS -->
                <div class="loader-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12 6 12 12 16 14"></polyline>
                    </svg>
                </div>
                <h2 class="status-title">Estructura Lista</h2>
                <p class="status-msg">
                    ¡La base de tu plan ya ha sido creada! <br>
                    Nuestro equipo está afinando los últimos detalles de tu programación. <br>
                    <strong>Estará disponible para descarga en:</strong>
                </p>

                <div class="timer-container" id="timer">
                    <div class="timer-box">
                        <span class="timer-val" id="hours">00</span>
                        <span class="timer-label">Horas</span>
                    </div>
                    <div class="timer-box">
                        <span class="timer-val" id="minutes">00</span>
                        <span class="timer-label">Minutos</span>
                    </div>
                    <div class="timer-box">
                        <span class="timer-val" id="seconds">00</span>
                        <span class="timer-label">Segundos</span>
                    </div>
                </div>

                <script>
                    function updateTimer() {
                        const targetDate = <?php echo $target_time; ?> * 1000;
                        const now = new Date().getTime();
                        const distance = targetDate - now;

                        if (distance < 0) {
                            location.reload();
                            return;
                        }

                        const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                        const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                        const seconds = Math.floor((distance % (1000 * 60)) / 1000);

                        document.getElementById("hours").innerText = String(hours).padStart(2, '0');
                        document.getElementById("minutes").innerText = String(minutes).padStart(2, '0');
                        document.getElementById("seconds").innerText = String(seconds).padStart(2, '0');
                    }
                    setInterval(updateTimer, 1000);
                    updateTimer();
                </script>
            <?php else: ?>
                <!-- CASO 3: EL PLAN YA ESTÁ DISPONIBLE -->
                <div class="loader-icon" style="color: #28a745;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                    </svg>
                </div>
                <h2 class="status-title">¡Plan Disponible!</h2>
                <p class="status-msg">
                    Tu estrategia personalizada está lista para ser ejecutada. <br>
                    Accede ahora para ver tus rutinas y dieta.
                </p>
                <a href="<?php echo get_permalink($plan_post->ID); ?>" class="btn-action">
                    VER MI PLAN AHORA
                </a>
            <?php endif; ?>
        <?php endif; ?>

        <div style="margin-top: 40px; border-top: 1px solid var(--card-border); padding-top: 30px;">
            <a href="area_privada.php" style="color: var(--text-secondary); text-decoration:none; font-size: 0.9rem;">
                ← Volver a mi cuenta
            </a>
        </div>
    </div>
</div>

<?php get_footer(); ?>
