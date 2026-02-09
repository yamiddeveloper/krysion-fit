<?php
/**
 * Template Name: KrysionFit - Gestión Elite
 */

// 1. CONFIGURACIÓN GLOBAL
$total_semanas = 6; 

// 2. SEGURIDAD Y VALIDACIÓN DE ACCESO
$post_id = get_the_ID();
$current_user_id = get_current_user_id();
$es_coach = current_user_can('administrator');

$cliente_asignado = get_field('cliente', $post_id);
$id_cliente_plan = 0;

if (is_numeric($cliente_asignado)) { $id_cliente_plan = $cliente_asignado; } 
elseif (is_array($cliente_asignado) && isset($cliente_asignado['ID'])) { $id_cliente_plan = $cliente_asignado['ID']; } 
elseif (is_object($cliente_asignado) && isset($cliente_asignado->ID)) { $id_cliente_plan = $cliente_asignado->ID; }

if (!is_user_logged_in()) { 
    header("Location: /encuesta/login.php"); 
    exit; 
}
if (!$es_coach && (int)$id_cliente_plan !== (int)$current_user_id) { wp_redirect(home_url()); exit; }

// 3. CÁLCULO DE LAS 24 HORAS (CRONÓMETRO)
$fecha_creacion   = get_post_time('U', true, $post_id); 
$ahora            = time();
$segundos_24h     = 24 * 60 * 60;
$tiempo_limite    = $fecha_creacion + $segundos_24h;
$faltan_segundos  = $tiempo_limite - $ahora;

$manual_bypass = (get_post_meta($post_id, 'kf_countdown_bypass', true) === 'yes');
$plan_disponible = ($faltan_segundos <= 0 || $es_coach || $manual_bypass);

// 4. OBTENER DATOS DE LA ENCUESTA (CUSTOM DB)
require_once dirname(dirname(dirname(__DIR__))) . '/encuesta/db.php';
$atleta_data = null;
$u_info = get_userdata($id_cliente_plan);
if ($u_info) {
    $stmt = $pdo->prepare("SELECT * FROM planes_personalizados WHERE email = ?");
    $stmt->execute([$u_info->user_email]);
    $atleta_data = $stmt->fetch(PDO::FETCH_ASSOC);
}

// 5. RECUPERAR LOGS DE SEGUIMIENTO (PERSISTENCIA)
$saved_logs = [];
$dias_slugs = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo'];
for ($w = 1; $w <= $total_semanas; $w++) {
    foreach ($dias_slugs as $slug) {
        $log_ent = get_post_meta($post_id, "log_entrenamiento_{$w}_{$slug}", true);
        if ($log_ent) $saved_logs["ent_{$w}_{$slug}"] = $log_ent;
        
        $log_nut = get_post_meta($post_id, "log_nutricion_{$w}_{$slug}", true);
        if ($log_nut) $saved_logs["nut_{$w}_{$slug}"] = $log_nut;
    }
}

// 6. LÓGICA DE PROCESAMIENTO (POST)
if ( $es_coach ) {
    if ( isset($_POST['action']) && $_POST['action'] == 'kf_save_full_plan' ) {
        $dias_slugs = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo'];
        for ($w = 1; $w <= $total_semanas; $w++) {
            // Sincronizar entrenamiento hacia ACF
            if (isset($_POST['entrenamiento'][$w])) {
                $data_ent = [];
                foreach ($dias_slugs as $slug) {
                    $data_ent[$slug] = sanitize_text_field($_POST['entrenamiento'][$w][$slug]['t']);
                    $data_ent['descripcion_' . $slug] = wp_kses_post($_POST['entrenamiento'][$w][$slug]['d']);
                    
                    // Guardar en campo ACF individual (rutina_lunes, rutina_martes, etc.)
                    update_field('rutina_' . $slug, $data_ent[$slug], $post_id);
                }
                // También guardar en campo de semana para compatibilidad
                update_field("semana_$w", $data_ent, $post_id);
            }
            
            // Sincronizar nutrición hacia ACF
            if (isset($_POST['nutricion'][$w])) {
                $data_nut = [];
                foreach ($dias_slugs as $slug) {
                    $data_nut[$slug] = sanitize_text_field($_POST['nutricion'][$w][$slug]['t']);
                    $data_nut['descripcion_' . $slug] = wp_kses_post($_POST['nutricion'][$w][$slug]['d']);
                    
                    // Guardar en campo ACF individual si existe
                    update_field('nutricion_' . $slug, $data_nut[$slug], $post_id);
                }
                // También guardar en campo de nutrición de semana
                update_field("nutricion_$w", $data_nut, $post_id);
            }
        }
        update_field('consejos_coach', wp_kses_post($_POST['consejos_coach']), $post_id);
        wp_redirect(get_permalink() . "?success=1");
        exit;
    }
}

get_header(); 

$titulo_plan = get_the_title();
$slugs_dias = ["lunes", "martes", "miercoles", "jueves", "viernes", "sabado", "domingo"];
$nombres_dias = ["LUNES", "MARTES", "MIÉRCOLES", "JUEVES", "VIERNES", "SÁBADO", "DOMINGO"];
$consejos_html = get_field('consejos_coach', $post_id) ?: 'Escribe aquí los consejos...';
?>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800&display=swap" rel="stylesheet">
<!-- Html2Pdf Library -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

<style>
  :root { --kf-orange: #F2600C; --kf-bg: #0D0D0D; --kf-card: #141414; --kf-line: #262626; --kf-text: #efefef; --kf-gray: #888; }
  html, body { height: auto !important; min-height: 100vh !important; }
  body { display: flex !important; flex-direction: column !important; background: var(--kf-bg); color: var(--kf-text); font-family: 'Poppins', sans-serif; }
  .kf-wrapper-full { background: var(--kf-bg); width: 100%; padding: 60px 0 120px 0; min-height: 80vh; position: relative; flex: 1 0 auto; }
  .kf-container { max-width: 1200px; margin: 0 auto; padding: 0 20px; }
  
  /* Estilos del Cronómetro */
  .countdown-box { text-align: center; padding: 80px 40px; background: none; }
  #timer { font-size: clamp(48px, 10vw, 80px); font-weight: 800; color: #fff; margin: 20px 0; font-variant-numeric: tabular-nums; letter-spacing: 2px; }

  /* Elementos de la Interfaz */
  .kf-admin-bar { background: #000; border-left: 4px solid var(--kf-orange); padding: 20px 30px; border-radius: 12px; margin-bottom: 40px; display: flex; justify-content: space-between; align-items: center; }
  .kf-plan-header { text-align: center; margin-bottom: 40px; }
  .kf-plan-title { font-size: clamp(24px, 5vw, 42px); font-weight: 800; color: #fff; text-transform: uppercase; margin: 0; }
  .kf-plan-title span { color: var(--kf-orange); }
  .kf-nav-main { display: flex; justify-content: center; gap: 12px; margin-bottom: 15px; flex-wrap: wrap; }
  .nav-btn { background: var(--kf-card); border: 1px solid var(--kf-line); color: var(--kf-gray); padding: 12px 30px; border-radius: 50px; cursor: pointer; font-weight: 700; transition: 0.3s; }
  .nav-btn:hover{ background: var(--primary); }
  .nav-btn.active { background: var(--kf-orange); color: white; border-color: var(--kf-orange); }
  .kf-nav-weeks { display: flex; justify-content: center; gap: 8px; flex-wrap: wrap; margin-bottom: 30px; margin-top: 30px; }
  .week-btn { background: var(--kf-card); border: 1px solid var(--kf-line); color: var(--kf-gray); padding: 8px 18px; border-radius: 50px; cursor: pointer; font-size: 12px; font-weight: 600; }
  .week-btn.active { color: var(--kf-orange); border-color: var(--kf-orange); }
  .week-btn:hover{background: var(--primary); }
  .week-btn.active:hover {background: none; }
  .kf-main-grid { display: grid; grid-template-columns: 1.6fr 0.8fr; gap: 25px; align-items: start; }

  /* TABLAS LIMPIAS Y INPUTS VISIBLES */
  .kf-log-table { 
      border-collapse: separate !important; 
      border-spacing: 0 8px !important; 
      width: 100% !important; 
      margin-top: 10px;
  }
  .kf-log-input {
    width: 100%;
    background: #111 !important;
    border: 1px solid var(--kf-line) !important;
    color: #fff !important;
    padding: 10px 12px !important;
    border-radius: 8px !important;
    font-family: 'Poppins', sans-serif;
    font-size: 14px !important;
    transition: 0.3s;
    outline: none;
  }
  .kf-log-input:focus { border-color: var(--kf-orange) !important; background: #161616 !important; }
  
  .kf-log-table th { 
      text-transform: uppercase;
      font-size: 10px;
      letter-spacing: 1px;
      color: var(--kf-gray);
      padding: 0 10px 10px;
      font-weight: 800;
  }
  .kf-log-table td { padding: 0 5px; }

  /* NUEVO: ESTILO PARA DÍA COMPLETADO */
  .day-card { cursor: pointer; transition: transform 0.2s; }
  .day-card:hover { transform: translateY(-2px); border-color: var(--kf-orange); }
  .day-card.active { border-color: var(--kf-orange); background: rgba(242, 96, 12, 0.05); box-shadow: 0 0 15px rgba(242, 96, 12, 0.1); }
  .day-card.is-done { border-color: #10b981 !important; background: rgba(16, 185, 129, 0.05) !important; }
  .check-icon { float: right; color: #10b981; font-size: 16px; font-weight: bold; }
  .btn-uncheck-log {
      background: transparent;
      border: 1px solid #ef4444;
      color: #ef4444;
      padding: 10px 20px;
      border-radius: 50px;
      font-size: 11px;
      font-weight: 800;
      cursor: pointer;
      transition: 0.3s;
  }
  .btn-uncheck-log:hover {
      background: #ef4444;
      color: #fff;
      box-shadow: 0 5px 15px rgba(239, 68, 68, 0.2);
  }

  /* REDISEÑO: INFO ATLETA MODERNO Y COHERENTE (Grid) */
  .atleta-header-table {
      display: grid;
      grid-template-columns: 1.5fr 1fr 1fr 1fr;
      background: #111;
      border: 1px solid var(--kf-line);
      border-radius: 12px;
      margin: 20px auto 40px;
      max-width: 900px;
      overflow: hidden;
      box-shadow: 0 4px 20px rgba(0,0,0,0.3);
  }
  .atleta-table-col {
      padding: 20px 15px;
      text-align: center;
      border-right: 1px solid var(--kf-line);
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      gap: 5px;
  }
  .atleta-name-col { 
      background: rgba(242, 96, 12, 0.04); 
      align-items: flex-start;
      padding-left: 25px;
      text-align: left;
  }
  .atleta-table-col:last-child { border-right: none; }
  
  .atleta-table-label {
      display: flex;
      align-items: center;
      gap: 8px;
      font-size: 9px;
      font-weight: 800;
      color: var(--kf-orange);
      text-transform: uppercase;
      letter-spacing: 1.5px;
      opacity: 0.9;
  }
  .atleta-table-value {
      font-size: 15px;
      font-weight: 700;
      color: #fff;
      display: block;
  }

  /* RESPONSIVIDAD EQUILIBRADA */
  @media (max-width: 850px) {
      .atleta-header-table {
          grid-template-columns: repeat(3, 1fr);
      }
      .atleta-name-col {
          grid-column: 1 / -1;
          border-bottom: 1px solid var(--kf-line);
          border-right: none;
          padding: 18px 25px;
          text-align: center;
          align-items: center;
      }
      .atleta-table-col {
          border-bottom: none;
          padding: 15px 10px;
      }
      .atleta-table-col:nth-child(3) {
          border-right: 1px solid var(--kf-line);
      }
      .atleta-table-label { font-size: 10px; }
      .atleta-table-value { font-size: 14px; }
  }

  @media (max-width: 480px) {
      .atleta-header-table {
          margin: 10px auto 30px;
      }
      .atleta-name-col {
          padding: 15px 20px;
      }
      .atleta-table-col {
          padding: 12px 5px;
      }
      .atleta-table-label { 
          font-size: 8.5px; 
          gap: 4px;
          letter-spacing: 1px;
      }
      .atleta-table-label svg { width: 10px; height: 10px; }
      .atleta-table-value { font-size: 13px; }
  }

    .admin-float {
        position: fixed;
        bottom: 15px;
        left: 50%;
        transform: translateX(-50%);
        z-index: 1000;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 6px;
    }
    
    /* Botón principal */
    .btn-save-all {
        position: relative;
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 16px 32px;
        background: linear-gradient(135deg, var(--kf-orange, #ff6b35) 0%, #ff8c42 100%);
        color: white;
        border: none;
        border-radius: 50px;
        font-size: 15px;
        font-weight: 700;
        cursor: pointer;
        box-shadow: 
            0 4px 20px rgba(255, 107, 53, 0.4),
            0 2px 8px rgba(0, 0, 0, 0.2);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        overflow: hidden;
        letter-spacing: 0.3px;
    }
    
    .btn-save-all::before {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(135deg, #ff8c42 0%, #ffa042 100%);
        opacity: 0;
        transition: opacity 0.3s ease;
    }
    
    .btn-save-all:hover::before {
        opacity: 1;
    }
    
    .btn-save-all:hover {
        transform: translateY(-2px);
        box-shadow: 
            0 8px 28px rgba(255, 107, 53, 0.5),
            0 4px 12px rgba(0, 0, 0, 0.3);
    }
    
    .btn-save-all:active {
        transform: translateY(0);
        box-shadow: 
            0 2px 12px rgba(255, 107, 53, 0.4),
            0 1px 4px rgba(0, 0, 0, 0.2);
    }
    
    /* Contenido del botón */
    .btn-icon,
    .btn-text {
        position: relative;
        z-index: 2;
    }
    
    .btn-icon {
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .btn-text {
        white-space: nowrap;
    }
    
    /* Efecto de brillo */
    .btn-shine {
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(
            90deg,
            transparent,
            rgba(255, 255, 255, 0.3),
            transparent
        );
        z-index: 1;
        animation: shine 3s infinite;
    }
    
    @keyframes shine {
        0% {
            left: -100%;
        }
        20%, 100% {
            left: 100%;
        }
    }
    
    /* Estado de guardando */
    .btn-save-all.saving {
        pointer-events: none;
        opacity: 0.8;
    }
    
    .btn-save-all.saving .btn-icon svg {
        animation: rotate 1s linear infinite;
    }
    
    @keyframes rotate {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
    
    /* Indicador de estado */
    .save-status {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 10px 20px;
        background: #1a1a1a;
        border: 1px solid #4ade80;
        border-radius: 50px;
        color: #4ade80;
        font-size: 13px;
        font-weight: 600;
        opacity: 0;
        transform: translateY(10px);
        pointer-events: none;
        transition: all 0.3s ease;
        box-shadow: 0 4px 12px rgba(74, 222, 128, 0.2);
    }
    
    .save-status.show {
        opacity: 1;
        transform: translateY(0);
    }
    
    .save-status.error {
        border-color: #ef4444;
        color: #ef4444;
        box-shadow: 0 4px 12px rgba(239, 68, 68, 0.2);
    }
    
    .status-icon {
        flex-shrink: 0;
    }
    
    .save-status.error .status-icon {
        animation: shake 0.5s ease;
    }
    
    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        25% { transform: translateX(-5px); }
        75% { transform: translateX(5px); }
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .admin-float {
            bottom: 20px;
        }
        
        .btn-save-all {
            padding: 14px 24px;
            font-size: 14px;
        }
        
        .btn-text {
            display: none;
        }
        
        .btn-icon {
            margin: 0;
        }
        
        .save-status {
            font-size: 12px;
            padding: 8px 16px;
        }
    }
    
    /* Animación de entrada */
    @keyframes slideUp {
        from {
            opacity: 0;
            transform: translateX(-50%) translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateX(-50%) translateY(0);
        }
    }
    
    .admin-float {
        animation: slideUp 0.4s ease-out;
    }
    
    /* Accesibilidad */
    @media (prefers-reduced-motion: reduce) {
        .btn-save-all,
        .save-status {
            transition: none;
        }
        
        .btn-shine {
            animation: none;
        }
        
        .admin-float {
            animation: none;
        }
    }
    
    /* Backdrop blur sutil para mejor legibilidad */
    .admin-float::before {
        content: '';
        position: absolute;
        bottom: -10px;
        left: 50%;
        transform: translateX(-50%);
        width: 120%;
        height: 80px;
        background: radial-gradient(
            ellipse at center,
            rgba(0, 0, 0, 0.1) 0%,
            transparent 70%
        );
        z-index: -1;
        pointer-events: none;
        filter: blur(10px);
    }
  @media (max-width: 992px) { .kf-main-grid { grid-template-columns: 1fr; } }
  .day-card { background: var(--kf-card); border: 1px solid var(--kf-line); padding: 20px; border-radius: 15px; border-top: 4px solid var(--kf-orange); margin-bottom: 15px; position: relative; }
  .day-label { font-size: 11px; font-weight: 800; color: var(--kf-orange); text-transform: uppercase; margin-bottom: 10px; }
  .edit-t { font-size: 16px; font-weight: 700; color: #fff; margin-bottom: 5px; outline: none; }
  .edit-d { font-size: 13px; color: #bbb; line-height: 1.6; outline: none; min-height: 20px; }
  .is-hidden { display: none !important; }

  /* ESTILOS DE TÍTULO DE SEMANA */
  .kf-week-title-section {
    margin-bottom: 30px;
    text-align: center;
  }
  .kf-week-title {
    font-size: 2rem !important;
    font-weight: 800;
    color: #fff;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin: 0;
    padding: 20px 0;
    border-bottom: 2px solid var(--kf-line);
    position: relative;
  }
  .kf-week-title::after {
    content: '';
    position: absolute;
    bottom: -2px;
    left: 50%;
    transform: translateX(-50%);
    width: 80px;
    height: 2px;
    background: var(--kf-orange);
  }

  /* ESPACIADO ADICIONAL PARA SECCIONES */
  .mod-content {
    margin-bottom: 40px;
    padding-top: 20px;
  }

  /* ESTILOS DE PLAN GENERAL */
  .kf-plan-general-card {
      background: var(--kf-card);
      border: 1px solid var(--kf-line);
      border-radius: 15px;
      padding: 25px;
      margin-bottom: 25px;
      position: relative;
      overflow: hidden;
  }
  .kf-plan-general-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 4px;
      background: linear-gradient(90deg, var(--kf-orange) 0%, #ff8c42 100%);
  }
  .kf-plan-general-header {
      margin-bottom: 20px;
  }
  .kf-plan-general-title {
      display: flex;
      align-items: center;
      gap: 12px;
  }
  .kf-plan-general-icon {
      font-size: 24px;
      filter: drop-shadow(0 2px 4px rgba(242, 96, 12, 0.3));
  }
  .kf-plan-general-title h3 {
      margin: 0;
      font-size: 18px;
      font-weight: 800;
      color: #fff;
      text-transform: uppercase;
      letter-spacing: 1px;
  }
  .kf-plan-general-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
      gap: 20px;
  }
  .kf-plan-general-item {
      text-align: center;
      padding: 15px;
      background: rgba(255, 255, 255, 0.03);
      border: 1px solid rgba(255, 255, 255, 0.05);
      border-radius: 12px;
      transition: all 0.3s ease;
  }
  .kf-plan-general-item:hover {
      background: rgba(242, 96, 12, 0.05);
      border-color: rgba(242, 96, 12, 0.2);
      transform: translateY(-2px);
  }
  .kf-plan-general-label {
      font-size: 10px;
      font-weight: 800;
      color: var(--kf-orange);
      text-transform: uppercase;
      letter-spacing: 1px;
      margin-bottom: 8px;
  }
  .kf-plan-general-value {
      font-size: 20px;
      font-weight: 700;
      color: #fff;
      line-height: 1.2;
  }

  /* ESTILOS DE SEGUIMIENTO (LOGS) */
  .kf-log-container { margin-top: 30px; border-top: 1px solid var(--kf-line); padding-top: 25px; }
  .kf-log-title { font-size: 13px; font-weight: 800; color: #fff; margin-bottom: 15px; display: flex; align-items: center; gap: 10px; text-transform: uppercase; }
  .kf-log-title span { color: var(--kf-orange); }
  .kf-log-table { width: 100%; border-collapse: separate; border-spacing: 0 5px; }
  .kf-log-container { 
      grid-column: 1 / -1; 
      margin-top: 15px; 
      border: 1px solid var(--kf-line); 
      padding: 25px; 
      background: var(--kf-card); 
      border-radius: 15px;
      display: none; 
      grid-column: 1 / -1; /* Ocupar todo el ancho en el grid */
  }
  .kf-log-container.is-active { display: block; }
  .kf-log-table th { font-size: 9px; color: var(--kf-gray); text-align: left; padding: 5px 10px; letter-spacing: 1px; }
  
  /* Botones de Registro (Mejorados) */
  .btn-save-log {
      background: linear-gradient(135deg, var(--kf-orange) 0%, #ff8c42 100%);
      color: white;
      border: none;
      padding: 12px 28px;
      border-radius: 50px;
      font-weight: 700;
      font-size: 12px;
      text-transform: uppercase;
      letter-spacing: 1px;
      cursor: pointer;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      display: inline-flex;
      align-items: center;
      gap: 10px;
      box-shadow: 0 4px 15px rgba(242, 96, 12, 0.25);
      position: relative;
      overflow: hidden;
  }
  .btn-save-log:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 25px rgba(242, 96, 12, 0.4);
      filter: brightness(1.1);
  }
  .btn-save-log:active {
      transform: translateY(0);
  }
  .btn-save-log::after {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
      transition: 0.5s;
  }
  .btn-save-log:hover::after {
      left: 100%;
  }

  /* RESPONSIVE TRACKING TABLES */
  @media (max-width: 768px) {
    .kf-log-table, 
    .kf-log-table reality, 
    .kf-log-table thead, 
    .kf-log-table tbody, 
    .kf-log-table th, 
    .kf-log-table td, 
    .kf-log-table tr { 
      display: block !important; 
    }

    .kf-log-table thead tr { 
      position: absolute !important;
      top: -9999px !important;
      left: -9999px !important;
    }

    .kf-log-table tr { 
      border: 1px solid var(--kf-line) !important;
      margin-bottom: 15px !important;
      border-radius: 12px !important;
      padding: 15px !important;
      background: rgba(255,255,255,0.03) !important;
      box-shadow: 0 4px 10px rgba(0,0,0,0.2) !important;
    }

    .kf-log-table td { 
      border: none !important;
      position: relative !important;
      padding-left: 120px !important; 
      margin-bottom: 8px !important;
      text-align: left !important;
      min-height: 45px !important;
      display: flex !important;
      align-items: center !important;
      justify-content: flex-start !important;
      width: 100% !important;
    }

    .kf-log-table td[width] { width: 100% !important; }

    .kf-log-table td:last-child {
        margin-bottom: 0 !important;
    }

    .kf-log-table td:before { 
      content: attr(data-label);
      position: absolute !important;
      left: 15px !important;
      width: 100px !important; 
      white-space: nowrap !important;
      text-align: left !important;
      font-weight: 800 !important;
      font-size: 10px !important;
      color: var(--kf-orange) !important;
      text-transform: uppercase !important;
      letter-spacing: 0.5px !important;
    }

    /* Input adjustments for mobile */
    .kf-log-table .kf-log-input {
      font-size: 14px !important;
      height: 42px !important;
      padding: 8px 12px !important;
      border-radius: 8px !important;
      text-align: left !important;
      background: rgba(0,0,0,0.3) !important;
      border: 1px solid rgba(255,255,255,0.05) !important;
      flex: 1;
    }
    
    .kf-log-container {
        padding: 15px !important;
    }
    
    .kf-log-title {
        font-size: 11px !important;
    }
  }

    .chat-container {
        display: flex;
        flex-direction: column;
        height: 600px;
        background: #1a1a1a;
        border-radius: 12px;
        border: 1px solid #2d2d2d;
        overflow: hidden;
    }
    
    /* Header */
    .chat-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 16px 20px;
        background: linear-gradient(135deg, var(--kf-orange, #ff6b35) 0%, #ff8c42 100%);
        color: white;
        border-bottom: 1px solid rgba(255, 107, 53, 0.3);
    }
    
    .chat-header-content {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .chat-icon {
        font-size: 20px;
        animation: pulse 2s ease-in-out infinite;
    }
    
    .chat-title {
        font-size: 13px;
        font-weight: 700;
        letter-spacing: 0.5px;
    }
    
    .chat-status {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 11px;
        opacity: 0.95;
    }
    
    .status-dot {
        width: 8px;
        height: 8px;
        background: #4ade80;
        border-radius: 50%;
        animation: blink 2s ease-in-out infinite;
        box-shadow: 0 0 8px rgba(74, 222, 128, 0.6);
    }
    
    .status-text {
        font-weight: 500;
    }
    
    /* Mensajes */
    .chat-messages {
        flex: 1;
        overflow-y: auto;
        padding: 20px;
        background: #0d0d0d;
        scroll-behavior: smooth;
    }
    
    .chat-messages::-webkit-scrollbar {
        width: 6px;
    }
    
    .chat-messages::-webkit-scrollbar-track {
        background: transparent;
    }
    
    .chat-messages::-webkit-scrollbar-thumb {
        background: #404040;
        border-radius: 3px;
    }
    
    .chat-messages::-webkit-scrollbar-thumb:hover {
        background: #525252;
    }
    
    /* Estado vacío */
    .chat-empty-state {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        height: 100%;
        color: #6b7280;
        text-align: center;
    }
    
    .empty-icon {
        font-size: 48px;
        margin-bottom: 12px;
        opacity: 0.3;
    }
    
    .chat-empty-state p {
        margin: 0 0 4px 0;
        font-weight: 600;
        color: #9ca3af;
    }
    
    .chat-empty-state small {
        font-size: 12px;
        opacity: 0.6;
    }
    
    /* Mensajes */
    .msg-wrapper {
        display: flex;
        margin-bottom: 16px;
        animation: slideIn 0.3s ease-out;
    }
    
    .msg-wrapper.me {
        justify-content: flex-end;
    }
    
    .msg-wrapper.other {
        justify-content: flex-start;
    }
    
    .msg {
        max-width: 70%;
        min-width: 120px;
    }
    
    .msg-wrapper.me .msg {
        background: linear-gradient(135deg, var(--kf-orange, #ff6b35) 0%, #ff8c42 100%);
        color: white;
        border-radius: 16px 16px 4px 16px;
        box-shadow: 0 2px 12px rgba(255, 107, 53, 0.25);
    }
    
    .msg-wrapper.other .msg {
        background: #1f1f1f;
        color: #e5e5e5;
        border-radius: 16px 16px 16px 4px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.3);
        border: 1px solid #2d2d2d;
    }
    
    .msg-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px 14px 6px 14px;
        gap: 8px;
    }
    
    .msg-author {
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        display: flex;
        align-items: center;
        gap: 4px;
    }
    
    .msg-wrapper.me .msg-author {
        opacity: 0.9;
    }
    
    .msg-wrapper.other .msg-author {
        color: var(--kf-orange, #ff6b35);
    }
    
    .coach-badge {
        font-size: 12px;
    }
    
    .msg-time {
        font-size: 9px;
        opacity: 0.6;
        font-weight: 500;
    }
    
    .msg-content {
        padding: 0 14px 12px 14px;
        font-size: 14px;
        line-height: 1.5;
        word-wrap: break-word;
    }
    
    /* Input */
    .chat-input-area {
        padding: 16px 20px;
        background: #1a1a1a;
        border-top: 1px solid #2d2d2d;
    }
    
    .input-wrapper {
        display: flex;
        gap: 10px;
        align-items: center;
    }
    
    .chat-input {
        flex: 1;
        padding: 12px 16px;
        border: 2px solid #2d2d2d;
        border-radius: 24px;
        font-size: 14px;
        outline: none;
        transition: all 0.2s ease;
        background: #0d0d0d;
        color: #e5e5e5;
    }
    
    .chat-input::placeholder {
        color: #6b7280;
    }
    
    .chat-input:focus {
        border-color: var(--kf-orange, #ff6b35);
        background: #1a1a1a;
        box-shadow: 0 0 0 3px rgba(255, 107, 53, 0.15);
    }
    
    .send-btn {
        width: 44px;
        height: 44px;
        border-radius: 50%;
        border: none;
        background: linear-gradient(135deg, var(--kf-orange, #ff6b35) 0%, #ff8c42 100%);
        color: white;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s ease;
        box-shadow: 0 2px 12px rgba(255, 107, 53, 0.35);
    }
    
    .send-btn:hover {
        transform: scale(1.05);
        box-shadow: 0 4px 16px rgba(255, 107, 53, 0.45);
    }
    
    .send-btn:active {
        transform: scale(0.95);
    }
    
    .send-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
        transform: none;
    }
    
    /* Animaciones */
    @keyframes pulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.1); }
    }
    
    @keyframes blink {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.5; }
    }
    
    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .chat-container {
            height: 500px;
            border-radius: 8px;
        }
        
        .msg {
            max-width: 85%;
        }
        
        .chat-header {
            padding: 14px 16px;
        }
        
        .chat-messages {
            padding: 16px;
        }
        
        .chat-input-area {
            padding: 12px 16px;
        }
    }
    
    /* PDF TEMPLATE STYLES - Hidden from viewport */
    #pdf-hidden-container { position: absolute; top: 0; left: 0; width: 800px; color: #1a1a1a; background: #fff; font-family: 'Poppins', sans-serif; visibility: hidden; z-index: -1; pointer-events: none; height: 0; overflow: hidden; }
    #pdf-content { visibility: visible; }
    .pdf-page { padding: 25px; box-sizing: border-box; page-break-after: always; color: #1a1a1a; background: #fff; }
    .pdf-header { border-bottom: 2px solid #F2600C; padding-bottom: 12px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; }
    .pdf-logo { font-size: 22px; font-weight: 800; color: #F2600C; font-style: italic; }
    .pdf-title { font-size: 16px; font-weight: 700; color: #333; }
    .pdf-atleta-info { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin-bottom: 20px; background: #f9f9f9; padding: 12px; border-radius: 8px; }
    .pdf-info-item { display: flex; flex-direction: column; }
    .pdf-info-label { font-size: 8px; font-weight: 800; color: #F2600C; text-transform: uppercase; }
    .pdf-info-value { font-size: 13px; font-weight: 600; color: #333; }
    .pdf-section-title { font-size: 14px; font-weight: 800; color: #fff; background: #1a1a1a; padding: 6px 12px; margin: 15px 0 8px 0; display: inline-block; border-radius: 4px; }
    .pdf-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; table-layout: fixed; }
    .pdf-table th { background: #f2f2f2; color: #333; font-weight: 800; font-size: 9px; text-transform: uppercase; text-align: left; padding: 8px; border: 1px solid #ddd; }
    .pdf-table td { padding: 8px; border: 1px solid #ddd; vertical-align: top; font-size: 11px; word-wrap: break-word; }
    .pdf-day-row { background: #fff; }
    .pdf-day-name { font-weight: 800; color: #F2600C; width: 90px; }
    .pdf-plan-title { font-weight: 700; display: block; margin-bottom: 2px; font-size: 12px; }
    .pdf-plan-desc { font-size: 10px; color: #666; line-height: 1.3; }
    .pdf-advice { margin-top: 20px; border-top: 1px solid #ddd; padding-top: 15px; }
    .pdf-advice-title { font-size: 13px; font-weight: 800; color: #F2600C; margin-bottom: 8px; }
    .pdf-footer { margin-top: 25px; text-align: center; font-size: 9px; color: #999; }
</style>

<div class="kf-wrapper-full">
    <div class="kf-container">

        <?php if (!$plan_disponible): ?>
            <div class="countdown-box">
                <h2 style="color:var(--kf-orange); font-weight:800;"> PLAN EN PREPARACIÓN</h2>
                <p>Tu plan personalizado estará listo en:</p>
                <div id="timer">00h:00m:00s</div>
            </div>

            <script>
                (function() {
                    let seconds = <?php echo max(0, $faltan_segundos); ?>;
                    const display = document.getElementById('timer');
                    const interval = setInterval(() => {
                        if (seconds <= 0) {
                            clearInterval(interval);
                            window.location.reload();
                            return;
                        }
                        const h = Math.floor(seconds / 3600);
                        const m = Math.floor((seconds % 3600) / 60);
                        const s = seconds % 60;
                        display.innerText = `${String(h).padStart(2,'0')}h:${String(m).padStart(2,'0')}m:${String(s).padStart(2,'0')}s`;
                        seconds--;
                    }, 1000);
                })();
            </script>

        <?php else: ?>
            <?php if($es_coach): ?>
            <div class="kf-admin-bar">
                <div>
                    <span style="font-weight:800; font-size:10px; color:var(--kf-orange);">MODO EDICIÓN</span>
                    <h3 style="margin:0; font-size:16px; color:#fff;">Gestión del Plan</h3>
                </div>
                <div style="display: flex; gap: 10px; align-items: center;">
                    <button type="button" class="btn-sync-acf" onclick="guardarHaciaACF()" style="background: #10b981; color: white; border: none; padding: 8px 16px; border-radius: 50px; font-size: 11px; font-weight: 700; cursor: pointer; transition: all 0.3s;">
                        � Guardar en ACF
                    </button>
                    <div style="font-size:12px; color:var(--kf-gray);">Cambios globales activos</div>
                </div>
            </div>
            <?php endif; ?>

            <header class="kf-plan-header">
                <h1 class="kf-plan-title">
                    <?php 
                        $words = explode(' ', $titulo_plan);
                        if (count($words) > 1) {
                            $last_word = array_pop($words);
                            echo implode(' ', $words) . ' <span>' . $last_word . '</span>';
                        } else { echo '<span>' . $titulo_plan . '</span>'; }
                    ?>
                </h1>
                </h1>
                
                <?php if ($atleta_data): ?>
                    <div class="atleta-header-table">
                        <div class="atleta-table-col atleta-name-col">
                            <span class="atleta-table-label">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                                Atleta
                            </span>
                            <span class="atleta-table-value"><?php echo esc_html($u_info->display_name); ?></span>
                        </div>
                        <div class="atleta-table-col">
                            <span class="atleta-table-label">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path><line x1="7" y1="7" x2="7.01" y2="7"></line></svg>
                                Peso
                            </span>
                            <span class="atleta-table-value"><?php echo $atleta_data['weight']; ?> kg</span>
                        </div>
                        <div class="atleta-table-col">
                            <span class="atleta-table-label">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
                                Altura
                            </span>
                            <span class="atleta-table-value"><?php echo $atleta_data['height']; ?> cm</span>
                        </div>
                        <div class="atleta-table-col">
                            <span class="atleta-table-label">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                                Edad
                            </span>
                            <span class="atleta-table-value"><?php echo $atleta_data['age']; ?> años</span>
                        </div>
                    </div>
                <?php else: ?>
                    <span style="color:var(--kf-gray); font-size:12px; display:block; margin-top:10px;">Atleta: <?php echo esc_html($u_info->display_name); ?></span>
                <?php endif; ?>
            </header>

            <div class="kf-nav-group">
                <div class="kf-nav-main">
                    <button class="nav-btn active" id="btn-ent" onclick="switchMod('entrenamiento')">🏋️ ENTRENAMIENTO</button>
                    <button class="nav-btn" id="btn-nut" onclick="switchMod('nutricion')">🥗 NUTRICIÓN</button>
                </div>
                <div class="kf-nav-weeks">
                    <?php 
                    $titulos_semanas = [
                        1 => 'Semana 1 – Adaptación',
                        2 => 'Semana 2 – Consolidación inicial', 
                        3 => 'Semana 3 – Primeros cambios visibles',
                        4 => 'Semana 4 – Resultados parciales',
                        5 => 'Semana 5 – Máximo rendimiento',
                        6 => 'Semana 6 – Definición final'
                    ];
                    for($i=1; $i<=$total_semanas; $i++): 
                    ?>
                        <button class="week-btn <?php echo ($i==1)?'active':''; ?>" data-week="<?php echo $i; ?>" onclick="switchWeek(<?php echo $i; ?>)"><?php echo $titulos_semanas[$i]; ?></button>
                    <?php endfor; ?>
                </div>
            </div>
            
            <div class="kf-main-grid">
                <div class="kf-col-left">
                    <div id="content-area">
                        <?php for ($w = 1; $w <= $total_semanas; $w++): ?>
                            
                            <div id="week-ent-<?php echo $w; ?>" class="mod-content mod-ent <?php echo ($w > 1) ? 'is-hidden' : ''; ?>">
                                <!-- Título de la Semana -->
                                <div class="kf-week-title-section">
                                    <h2 class="kf-week-title"><?php echo $titulos_semanas[$w]; ?></h2>
                                </div>
                                
                                <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap:15px;">
                                    <?php 
                                    // Obtener datos directamente de los campos ACF individuales
                                    $plan = get_field("semana_$w", $post_id);
                                    foreach($slugs_dias as $idx => $slug): 
                                        $completado = get_post_meta($post_id, "completado_{$w}_{$slug}", true); // Lógica de chulito
                                    ?>
                                        <div class="day-card <?php echo $completado ? 'is-done' : ''; ?>" 
                                             data-type="ent" 
                                             data-week="<?php echo $w; ?>" 
                                             data-day="<?php echo $slug; ?>"
                                             data-name="<?php echo $nombres_dias[$idx]; ?>"
                                             onclick="selectDay('<?php echo $slug; ?>', '<?php echo $nombres_dias[$idx]; ?>', this)">
                                            <div class="day-label">
                                                <?php echo $nombres_dias[$idx]; ?>
                                                <span class="check-icon"><?php echo $completado ? '✔' : ''; ?></span>
                                            </div>
                                            <div class="edit-t" contenteditable="<?php echo $es_coach?'true':'false'; ?>" data-field="t"><?php echo esc_html($plan[$slug] ?: 'Descanso'); ?></div>
                                            <div class="edit-d" contenteditable="<?php echo $es_coach?'true':'false'; ?>" data-field="d"><?php echo wp_kses_post($plan['descripcion_'.$slug] ?: ''); ?></div>
                                        </div>
                                    <?php endforeach; ?>
                                
                                    <div class="kf-log-container kf-log-ent-container">
                                        <div class="kf-log-title"><span>📈</span> <span class="log-title-text-ent">Registro de Progreso - Hoy</span></div>
                                        <table class="kf-log-table">
                                            <thead>
                                                <tr><th>Ejercicio</th><th width="80">Peso (kg)</th><th width="60">Reps</th><th width="60">RPE</th><th>Notas</th></tr>
                                            </thead>
                                            <tbody class="tbody-log-ent">
                                            </tbody>
                                        </table>
                                        <div style="display:flex; align-items:center; justify-content: space-between; gap:15px; margin-top: 20px;">
                                            <button class="btn-save-log" onclick="saveContextLog('ent')">
                                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
                                                Guardar Sesión de Entrenamiento
                                            </button>
                                            <button class="btn-uncheck-log is-hidden" onclick="uncheckActiveDay('ent', this)">✕ Quitar Completado</button>
                                        </div>
                                    </div>
                                </div>
                                
                            </div>

                            <div id="week-nut-<?php echo $w; ?>" class="mod-content mod-nut is-hidden">
                                <!-- Título de la Semana -->
                                <div class="kf-week-title-section">
                                    <h2 class="kf-week-title"><?php echo $titulos_semanas[$w]; ?></h2>
                                </div>
                                
                                <!-- Nutrición - Plan General -->
                                <div class="kf-plan-general-card">
                                    <div class="kf-plan-general-header">
                                        <div class="kf-plan-general-title">
                                            <span class="kf-plan-general-icon">🥗</span>
                                            <h3>NUTRICIÓN - PLAN GENERAL</h3>
                                        </div>
                                    </div>
                                    <div class="kf-plan-general-grid">
                                        <div class="kf-plan-general-item">
                                            <div class="kf-plan-general-label">CALORÍAS</div>
                                            <div class="kf-plan-general-value">2500 kcal</div>
                                        </div>
                                        <div class="kf-plan-general-item">
                                            <div class="kf-plan-general-label">PROTEÍNAS</div>
                                            <div class="kf-plan-general-value">150g</div>
                                        </div>
                                        <div class="kf-plan-general-item">
                                            <div class="kf-plan-general-label">CARBOHIDRATOS</div>
                                            <div class="kf-plan-general-value">250g</div>
                                        </div>
                                        <div class="kf-plan-general-item">
                                            <div class="kf-plan-general-label">GRASAS</div>
                                            <div class="kf-plan-general-value">85g</div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap:15px;">
                                    <?php 
                                    $plan_nut = get_field("nutricion_$w", $post_id);
                                    foreach($slugs_dias as $idx => $slug): 
                                        $completado_nut = get_post_meta($post_id, "completado_nut_{$w}_{$slug}", true);
                                    ?>
                                        <div class="day-card <?php echo $completado_nut ? 'is-done' : ''; ?>" 
                                             data-type="nut" 
                                             data-week="<?php echo $w; ?>" 
                                             data-day="<?php echo $slug; ?>"
                                             data-name="<?php echo $nombres_dias[$idx]; ?>"
                                             onclick="selectDay('<?php echo $slug; ?>', '<?php echo $nombres_dias[$idx]; ?>', this)">
                                            <div class="day-label">
                                                <?php echo $nombres_dias[$idx]; ?>
                                                <span class="check-icon"><?php echo $completado_nut ? '✔' : ''; ?></span>
                                            </div>
                                            <div class="edit-t" contenteditable="<?php echo $es_coach?'true':'false'; ?>" data-field="t"><?php echo esc_html($plan_nut[$slug] ?: 'Plan Dieta'); ?></div>
                                            <div class="edit-d" contenteditable="<?php echo $es_coach?'true':'false'; ?>" data-field="d"><?php echo wp_kses_post($plan_nut['descripcion_'.$slug] ?: ''); ?></div>
                                        </div>
                                    <?php endforeach; ?>

                                    <div class="kf-log-container kf-log-nut-container">
                                        <div class="kf-log-title"><span>🥗</span> <span class="log-title-text-nut">Diario de Alimentación</span></div>
                                        <table class="kf-log-table">
                                            <thead>
                                                <tr><th>Comida</th><th width="100">Hora</th><th>Detalle/Porción</th><th>Notas</th></tr>
                                            </thead>
                                            <tbody class="tbody-log-nut">
                                                <?php $diario = ['Desayuno', 'Almuerzo', 'Snack 1', 'Cena', 'Snack 2']; 
                                                foreach($diario as $meal): ?>
                                                <tr class="row-log-nut">
                                                    <td data-label="Comida"><input type="text" class="kf-log-input" name="food" value="<?php echo $meal; ?>"></td>
                                                    <td data-label="Hora"><input type="time" class="kf-log-input" name="time"></td>
                                                    <td data-label="Detalle"><input type="text" class="kf-log-input" name="portion" placeholder="Ej: 150g proteína + ensalada"></td>
                                                    <td data-label="Notas"><input type="text" class="kf-log-input" name="n" placeholder="..."></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                        <div style="display:flex; align-items:center; justify-content: space-between; gap:15px; margin-top: 20px;">
                                            <button class="btn-save-log" onclick="saveContextLog('nut')">
                                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
                                                Guardar Diario de Nutrición
                                            </button>
                                            <button class="btn-uncheck-log is-hidden" onclick="uncheckActiveDay('nut', this)">✕ Quitar Completado</button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        <?php endfor; ?>
                    </div>
                </div>
                

                <div class="kf-col-right" style="position: sticky; top: 20px; align-self: flex-start;">
                    <div class="chat-container">
                        <!-- Header mejorado -->
                        <div class="chat-header">
                            <div class="chat-header-content">
                                <span class="chat-icon">💬</span>
                                <span class="chat-title">FEEDBACK DIRECTO</span>
                            </div>
                            <div class="chat-status">
                                <span class="status-dot"></span>
                                <span class="status-text">En línea</span>
                            </div>
                        </div>
                    
                        <!-- Área de mensajes con scroll suave -->
                        <div class="chat-messages" id="chat-box">
                            <?php $msgs = get_comments(['post_id' => $post_id, 'order' => 'ASC']); ?>
                            <?php if(empty($msgs)): ?>
                                <div class="chat-empty-state">
                                    <div class="empty-icon">💭</div>
                                    <p>No hay mensajes aún</p>
                                    <small>Inicia la conversación</small>
                                </div>
                            <?php else: ?>
                                <?php foreach($msgs as $m): ?>
                                    <div class="msg-wrapper <?php echo ($m->user_id == $current_user_id) ? 'me' : 'other'; ?>">
                                        <div class="msg">
                                            <div class="msg-header">
                                                <span class="msg-author">
                                                    <?php if(user_can($m->user_id, 'administrator')): ?>
                                                        <span class="coach-badge">🏅</span>
                                                        COACH
                                                    <?php else: ?>
                                                        <?php echo esc_html($m->comment_author); ?>
                                                    <?php endif; ?>
                                                </span>
                                                <span class="msg-time"><?php echo human_time_diff(strtotime($m->comment_date), current_time('timestamp')); ?> atrás</span>
                                            </div>
                                            <div class="msg-content">
                                                <?php echo esc_html($m->comment_content); ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    
                        <!-- Input mejorado con mejor UX -->
                        <form class="chat-input-area" id="chat-form">
                            <div class="input-wrapper">
                                <input 
                                    type="text" 
                                    id="chat-input" 
                                    class="chat-input" 
                                    placeholder="Escribe tu mensaje..." 
                                    required
                                    autocomplete="off"
                                >
                                <button type="submit" class="send-btn" aria-label="Enviar mensaje">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z"/>
                                    </svg>
                                </button>
                            </div>
                        </form>
                    </div>
                    
                    <div style="margin-top:20px; background:var(--kf-card); padding:20px; border-radius:15px; border:1px solid var(--kf-line);">
                        <h4 style="font-size:12px; color:var(--kf-orange); margin:0 0 10px 0;">🧠 NOTAS PRIVADAS / CONSEJOS</h4>
                        <div id="consejos-area" contenteditable="<?php echo $es_coach?'true':'false'; ?>" style="font-size:13px; color:#ccc; min-height:80px; outline:none;">
                            <?php echo wp_kses_post($consejos_html); ?>
                        </div>
                    </div>
                    <div style="width: 100%; margin-top: 5%;">
                        <button id="btn-download-pdf" onclick="downloadPDF()" style="background: var(--primary); padding: 3%; border-radius: 20px; color: white; border: none; width: 100%; cursor: pointer; font-weight: 700; transition: transform 0.2s;">
                            Descargar en PDF 📕
                        </button>
                    </div>
                </div>
            </div>

            <?php if($es_coach): ?>
            
            <div class="admin-float">
                <button class="btn-save-all" onclick="savePlan()">
                    <span class="btn-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                            <polyline points="17 21 17 13 7 13 7 21"></polyline>
                            <polyline points="7 3 7 8 15 8"></polyline>
                        </svg>
                    </span>
                    <span class="btn-text">Guardar Todo el Plan</span>
                    <span class="btn-shine"></span>
                </button>
                
                <!-- Indicador de guardado -->
                <div class="save-status" id="saveStatus">
                    <svg class="status-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                    <span class="status-text">Cambios guardados</span>
                </div>
            </div>
            <form id="form-hidden" method="POST" style="display:none;">
                <input type="hidden" name="action" value="kf_save_full_plan">
                <div id="form-content"></div>
            </form>
            <?php endif; ?>

        <?php endif; ?>
    </div>
</div>

<script>
// ============================================================================
// KRYSIONFIT - GESTIÓN ELITE - JAVASCRIPT MEJORADO
// ============================================================================

// Variables globales de estado
const KF_STATE = {
    activeMod: 'entrenamiento',
    activeWeek: 1,
    totalWeeks: <?php echo $total_semanas; ?>,
    postId: <?php echo $post_id; ?>,
    ajaxUrl: '<?php echo admin_url('admin-ajax.php'); ?>',
    isCoach: <?php echo $es_coach ? 'true' : 'false'; ?>,
    unsavedChanges: false,
    activeDay: '',
    activeDayName: '',
    savedLogs: <?php echo json_encode($saved_logs); ?>,
    planData: <?php 
        $full_plan = [];
        for ($w = 1; $w <= $total_semanas; $w++) {
            $full_plan[$w] = [
                'ent' => get_field("semana_$w", $post_id),
                'nut' => get_field("nutricion_$w", $post_id)
            ];
        }
        echo json_encode($full_plan);
    ?>,
    atleta: <?php echo json_encode([
        'nombre' => $u_info->display_name,
        'peso' => $atleta_data['weight'] ?? '',
        'altura' => $atleta_data['height'] ?? '',
        'edad' => $atleta_data['age'] ?? '',
        'consejos' => $consejos_html
    ]); ?>
};

// ============================================================================
// GENERACIÓN DE PDF
// ============================================================================

/**
 * Genera y descarga el plan completo en PDF
 */
function downloadPDF() {
    const btn = document.getElementById('btn-download-pdf');
    const originalText = btn.innerHTML;
    
    // Feedback visual
    btn.innerHTML = 'Generando... ⏳';
    btn.disabled = true;
    showNotification('Generando tu plan en PDF...', 'info');

    const pdfContent = document.getElementById('pdf-content');
    pdfContent.innerHTML = ''; // Limpiar

    const diasNombres = ["LUNES", "MARTES", "MIÉRCOLES", "JUEVES", "VIERNES", "SÁBADO", "DOMINGO"];
    const diasSlugs = ["lunes", "martes", "miercoles", "jueves", "viernes", "sabado", "domingo"];

    // Construir el contenido para cada semana
    for (let w = 1; w <= KF_STATE.totalWeeks; w++) {
        const weekData = KF_STATE.planData[w];
        const page = document.createElement('div');
        page.className = 'pdf-page';

        // Header de la página
        let headerHtml = `
            <div class="pdf-header">
                <div class="pdf-logo">KRYSION FIT</div>
                <div class="pdf-title">PLAN SEMANA ${w}</div>
            </div>
            <div class="pdf-atleta-info">
                <div class="pdf-info-item"><span class="pdf-info-label">Atleta</span><span class="pdf-info-value">${KF_STATE.atleta.nombre}</span></div>
                <div class="pdf-info-item"><span class="pdf-info-label">Peso</span><span class="pdf-info-value">${KF_STATE.atleta.peso} kg</span></div>
                <div class="pdf-info-item"><span class="pdf-info-label">Altura</span><span class="pdf-info-value">${KF_STATE.atleta.altura} cm</span></div>
                <div class="pdf-info-item"><span class="pdf-info-label">Edad</span><span class="pdf-info-value">${KF_STATE.atleta.edad} años</span></div>
            </div>
        `;
        
        // Tabla de Entrenamiento
        let entHtml = `<div class="pdf-section-title">🏋️ ENTRENAMIENTO</div>
            <table class="pdf-table">
                <thead><tr><th width="120">Día</th><th>Rutina / Ejercicios</th></tr></thead>
                <tbody>`;

        diasSlugs.forEach((slug, idx) => {
            const title = weekData.ent[slug] || 'Descanso';
            const desc = weekData.ent['descripcion_' + slug] || '';
            entHtml += `
                <tr class="pdf-day-row">
                    <td class="pdf-day-name">${diasNombres[idx]}</td>
                    <td>
                        <span class="pdf-plan-title">${title}</span>
                        <div class="pdf-plan-desc">${desc}</div>
                    </td>
                </tr>`;
        });
        entHtml += `</tbody></table>`;

        // Tabla de Nutrición
        let nutHtml = `<div class="pdf-section-title">🥗 NUTRICIÓN</div>
            <table class="pdf-table">
                <thead><tr><th width="120">Día</th><th>Plan de Alimentación</th></tr></thead>
                <tbody>`;

        diasSlugs.forEach((slug, idx) => {
            const title = weekData.nut[slug] || 'Plan Estándar';
            const desc = weekData.nut['descripcion_' + slug] || '';
            nutHtml += `
                <tr class="pdf-day-row">
                    <td class="pdf-day-name">${diasNombres[idx]}</td>
                    <td>
                        <span class="pdf-plan-title">${title}</span>
                        <div class="pdf-plan-desc">${desc}</div>
                    </td>
                </tr>`;
        });
        nutHtml += `</tbody></table>`;

        // Consejos (solo en la última página o si hay mucho espacio)
        let footerHtml = `
            <div class="pdf-footer">
                Generado por Krysion Fit - Sistema de Gestión Elite
            </div>
        `;

        if (w === KF_STATE.totalWeeks && KF_STATE.atleta.consejos) {
           let consejos = `<div class="pdf-advice">
                <div class="pdf-advice-title">🧠 NOTAS DEL COACH</div>
                <div class="pdf-plan-desc" style="font-size:12px;">${KF_STATE.atleta.consejos}</div>
            </div>`;
           page.innerHTML = headerHtml + entHtml + nutHtml + consejos + footerHtml;
        } else {
           page.innerHTML = headerHtml + entHtml + nutHtml + footerHtml;
        }

        pdfContent.appendChild(page);
    }

    // Opciones de html2pdf
    const opt = {
        margin:       [0, 0],
        filename:     `Plan_KrysionFit_${KF_STATE.atleta.nombre.replace(/\s+/g, '_')}.pdf`,
        image:        { type: 'jpeg', quality: 0.98 },
        html2canvas:  { 
            scale: 2, 
            useCORS: true, 
            letterRendering: true,
            scrollY: 0,
            y: 0 
        },
        jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' },
        pagebreak:    { mode: ['css', 'legacy'] }
    };

    // Generar
    html2pdf().set(opt).from(pdfContent).save().then(() => {
        btn.innerHTML = originalText;
        btn.disabled = false;
        showNotification('¡PDF descargado con éxito!', 'success');
    }).catch(err => {
        console.error('Error PDF:', err);
        btn.innerHTML = originalText;
        btn.disabled = false;
        showNotification('Error al generar el PDF', 'error');
    });
}

// ============================================================================
// NAVEGACIÓN Y VISTAS
// ============================================================================

/**
 * Cambia entre modos (Entrenamiento / Nutrición)
 */
function switchMod(mod) {
    KF_STATE.activeMod = mod;
    
    // Actualizar botones de navegación
    document.querySelectorAll('.nav-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    
    const activeBtn = mod === 'entrenamiento' ? 'btn-ent' : 'btn-nut';
    document.getElementById(activeBtn).classList.add('active');
    
    renderView();
    
    // Analytics (opcional)
    console.log(`Modo cambiado a: ${mod}`);
}

/**
 * Cambia entre semanas
 */
function switchWeek(week) {
    if (week < 1 || week > KF_STATE.totalWeeks) {
        console.error('Semana inválida:', week);
        return;
    }
    
    KF_STATE.activeWeek = week;
    
    // Actualizar botones de semanas
    document.querySelectorAll('.week-btn').forEach(btn => {
        btn.classList.remove('active');
        if (parseInt(btn.dataset.week) === week) {
            btn.classList.add('active');
        }
    });
    
    renderView();
    
    console.log(`Semana activa: ${week}`);
}

/**
 * Renderiza la vista actual (modo + semana)
 */
function renderView() {
    // Ocultar todo el contenido
    document.querySelectorAll('.mod-content').forEach(content => {
        content.classList.add('is-hidden');
    });
    
    // Mostrar contenido específico
    const prefix = KF_STATE.activeMod === 'entrenamiento' ? 'ent' : 'nut';
    const targetId = `week-${prefix}-${KF_STATE.activeWeek}`;
    const targetElement = document.getElementById(targetId);
    
    if (targetElement) {
        targetElement.classList.remove('is-hidden');
        
        // El seguimiento siempre inicia oculto al cambiar de vista
        hideTracking();

        // Scroll suave al contenido
        targetElement.scrollIntoView({ 
            behavior: 'smooth', 
            block: 'nearest' 
        });
    } else {
        console.error('Elemento no encontrado:', targetId);
    }
}

/**
 * Selecciona un día específico y actualiza el tracker
 */
function selectDay(daySlug, dayName, element) {
    const isAlreadyActive = element.classList.contains('active');
    
    // Si ya estaba activo, lo desactivamos y ocultamos el seguimiento
    if (isAlreadyActive) {
        hideTracking();
        return;
    }

    // Verificar si es día de descanso
    const titleEl = element.querySelector('.edit-t');
    const descEl = element.querySelector('.edit-d');
    const title = titleEl ? (titleEl.innerText || titleEl.textContent || "").trim() : "";
    const desc = descEl ? (descEl.innerText || descEl.textContent || "").trim() : "";
    
    // Si es día de descanso (título es "Descanso" y no hay descripción), no mostrar seguimiento
    if (title.toLowerCase() === 'descanso' && !desc) {
        // Ocultar cualquier seguimiento activo
        hideTracking();
        return;
    }

    KF_STATE.activeDay = daySlug;
    KF_STATE.activeDayName = dayName;

    // Remover activo de otros dás en la misma vista
    const parent = element.closest('.mod-content');
    parent.querySelectorAll('.day-card').forEach(card => card.classList.remove('active'));
    
    // Marcar este como activo
    element.classList.add('active');
    
    // Buscar y mover el contenedor de log correspondiente
    const type = element.dataset.type;
    const prefix = type === 'ent' ? 'ent' : 'nut';
    const logContainer = parent.querySelector(`.kf-log-${prefix}-container`);
    
    if (logContainer) {
        // Lógica inteligente: insertar después del último elemento de la FILA actual
        const cards = Array.from(parent.querySelectorAll('.day-card'));
        
        // Primero ocultamos el log para que no afecte el cálculo de offsets
        logContainer.classList.remove('is-active');
        
        let lastInRow = element;
        const targetOffset = element.offsetTop;
        
        for (const card of cards) {
            // Si el card tiene el mismo offset top (está en la misma fila)
            if (card.offsetTop === targetOffset) {
                lastInRow = card;
            }
        }

        // Mover el contenedor justo después del último de la fila
        lastInRow.after(logContainer);
        logContainer.classList.add('is-active');
        
        // Mostrar/ocultar botón de "Quitar Completado" dentro del contenedor movido
        const uncheckBtn = logContainer.querySelector('.btn-uncheck-log');
        if (uncheckBtn) {
            if (element.classList.contains('is-done')) {
                uncheckBtn.classList.remove('is-hidden');
            } else {
                uncheckBtn.classList.add('is-hidden');
            }
        }
    }

    // Actualizar títulos de logs
    const logTitleEnt = parent.querySelector('.log-title-text-ent');
    const logTitleNut = parent.querySelector('.log-title-text-nut');
    if (logTitleEnt) logTitleEnt.innerText = `Registro de Progreso - ${dayName}`;
    if (logTitleNut) logTitleNut.innerText = `Diario de Alimentación - ${dayName}`;

    // Si es entrenamiento, poblar ejercicios (desde logs o desde plan)
    if (type === 'ent') {
        populateExercises(element);
    } else {
        // Si es nutrición, poblar desde logs si existen
        populateNutrition(element);
    }
}

/**
 * Parsea los ejercicios de la tarjeta (DESCRIPCIÓN) o carga del log persistente
 */
function populateExercises(dayCard) {
    const week = KF_STATE.activeWeek;
    const day = KF_STATE.activeDay;
    const logKey = `ent_${week}_${day}`;
    
    const parentId = `week-ent-${week}`;
    const parent = document.getElementById(parentId);
    if (!parent) return;

    const tbody = parent.querySelector('.tbody-log-ent');
    if (!tbody) return;

    // Limpiar el contenido actual
    tbody.innerHTML = '';

    const addRow = (data = {}) => {
        const row = document.createElement('tr');
        row.className = 'row-log-ent';
        row.innerHTML = `
            <td data-label="Ejercicio"><input type="text" class="kf-log-input" name="ex" value="${escapeHtml(data.exercise || '')}" placeholder="Ej: Prensa"></td>
            <td data-label="Peso (kg)" width="100"><input type="number" class="kf-log-input" name="w" value="${data.weight || ''}" placeholder="40"></td>
            <td data-label="Reps" width="80"><input type="number" class="kf-log-input" name="r" value="${data.reps || ''}" placeholder="8"></td>
            <td data-label="RPE" width="80"><input type="number" class="kf-log-input" name="rpe" value="${data.rpe || ''}" placeholder="7"></td>
            <td data-label="Notas"><input type="text" class="kf-log-input" name="n" value="${escapeHtml(data.notes || '')}" placeholder="..."></td>
        `;
        tbody.appendChild(row);
    };

    // INTENTAR CARGAR DESDE LOGS GUARDADOS
    if (KF_STATE.savedLogs[logKey] && Array.isArray(KF_STATE.savedLogs[logKey])) {
        KF_STATE.savedLogs[logKey].forEach(item => addRow(item));
        return;
    }

    // SI NO HAY LOGS, PARSEAR DE LA TARJETA
    const descEl = dayCard.querySelector('.edit-d');
    if (!descEl) return;

    const tempDiv = document.createElement('div');
    tempDiv.innerHTML = descEl.innerHTML;
    let cleanText = tempDiv.innerText || tempDiv.textContent || "";
    
    if (cleanText.toLowerCase().includes('descanso')) cleanText = "";

    if (!cleanText.trim()) {
        addRow();
        return;
    }

    const exercises = cleanText.split(/[,;\n]+/).map(ex => ex.trim()).filter(ex => ex !== '');
    if (exercises.length === 0) {
        addRow();
    } else {
        exercises.forEach(ex => addRow({ exercise: ex }));
    }
}

/**
 * Carga datos de nutrición persistentes
 */
function populateNutrition(dayCard) {
    const week = KF_STATE.activeWeek;
    const day = KF_STATE.activeDay;
    const logKey = `nut_${week}_${day}`;
    
    const parentId = `week-nut-${week}`;
    const parent = document.getElementById(parentId);
    if (!parent) return;

    const tbody = parent.querySelector('.tbody-log-nut');
    if (!tbody) return;

    // Si hay datos guardados, usarlos
    if (KF_STATE.savedLogs[logKey] && Array.isArray(KF_STATE.savedLogs[logKey])) {
        tbody.innerHTML = '';
        KF_STATE.savedLogs[logKey].forEach(item => {
            const row = document.createElement('tr');
            row.className = 'row-log-nut';
            row.innerHTML = `
                <td data-label="Comida"><input type="text" class="kf-log-input" name="food" value="${escapeHtml(item.food || '')}"></td>
                <td data-label="Hora"><input type="time" class="kf-log-input" name="time" value="${item.time || ''}"></td>
                <td data-label="Detalle"><input type="text" class="kf-log-input" name="portion" value="${escapeHtml(item.portion || '')}"></td>
                <td data-label="Notas"><input type="text" class="kf-log-input" name="n" value="${escapeHtml(item.notes || '')}"></td>
            `;
            tbody.appendChild(row);
        });
    } else {
        // Volver al default (esto ya está en el HTML, pero por si acaso refrescamos marcas)
        // No limpiamos porque ya tiene los inputs default de Desayuno, Almuerzo, etc.
    }
}

/**
 * Oculta cualquier sección de seguimiento activa
 */
function hideTracking() {
    KF_STATE.activeDay = '';
    KF_STATE.activeDayName = '';
    
    // Quitar clases activas de los días
    document.querySelectorAll('.day-card').forEach(card => {
        card.classList.remove('active');
    });
    
    // Ocultar contenedores de registro
    document.querySelectorAll('.kf-log-container').forEach(log => {
        log.classList.remove('is-active');
    });
}

/**
 * Limpia los inputs de una tabla de log
 */
function clearLogTable(type) {
    const parentId = `week-${type}-${KF_STATE.activeWeek}`;
    const parent = document.getElementById(parentId);
    if (!parent) return;

    const inputs = parent.querySelectorAll(type === 'ent' ? '.tbody-log-ent input' : '.tbody-log-nut input');
    inputs.forEach(input => {
        if (input.name !== 'food') { // No borrar nombres de comida en nutrición
            input.value = '';
        }
    });
}

// ============================================================================
// GUARDADO DE LOGS DE PROGRESO
// ============================================================================

/**
 * Guarda el log de entrenamiento o nutrición
 */
async function saveContextLog(type) {
    const prefix = type === 'ent' ? 'entrenamiento' : 'nutrición';
    const parentId = `week-${type}-${KF_STATE.activeWeek}`;
    const parent = document.getElementById(parentId);
    
    if (!parent) {
        showNotification('Error: Contenedor no encontrado', 'error');
        return;
    }
    
    const rows = parent.querySelectorAll(type === 'ent' ? '.row-log-ent' : '.row-log-nut');
    const data = [];
    
    // Recopilar datos según el tipo
    rows.forEach(row => {
        if (type === 'ent') {
            const exercise = row.querySelector('[name="ex"]')?.value.trim();
            if (exercise) {
                data.push({
                    exercise,
                    weight: row.querySelector('[name="w"]')?.value || '',
                    reps: row.querySelector('[name="r"]')?.value || '',
                    rpe: row.querySelector('[name="rpe"]')?.value || '',
                    notes: row.querySelector('[name="n"]')?.value || '',
                    week: KF_STATE.activeWeek,
                    date: new Date().toISOString()
                });
            }
        } else {
            const food = row.querySelector('[name="food"]')?.value.trim();
            if (food) {
                data.push({
                    food,
                    time: row.querySelector('[name="time"]')?.value || '',
                    portion: row.querySelector('[name="portion"]')?.value || '',
                    notes: row.querySelector('[name="n"]')?.value || '',
                    week: KF_STATE.activeWeek,
                    date: new Date().toISOString()
                });
            }
        }
    });
    
    if (data.length === 0) {
        showNotification(`No hay datos de ${prefix} para guardar`, 'warning');
        return;
    }
    
    // Preparar formulario
    const formData = new FormData();
    formData.append('action', type === 'ent' ? 'kf_save_workout_log' : 'kf_save_food_log');
    formData.append('post_id', KF_STATE.postId);
    formData.append('week', KF_STATE.activeWeek);
    formData.append('day', KF_STATE.activeDay);
    formData.append('payload', JSON.stringify(data));
    
    try {
        const response = await fetch(KF_STATE.ajaxUrl, {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification(`¡Progreso de ${prefix} guardado exitosamente!`, 'success');
            
            // Marcar día como completado visualmente
            markDayAsComplete(KF_STATE.activeWeek, type);
            
            // Mostrar botón de desmarcar
            const parent = document.getElementById(`week-${type}-${KF_STATE.activeWeek}`);
            const uncheckBtn = parent.querySelector('.btn-uncheck-log');
            if (uncheckBtn) uncheckBtn.classList.remove('is-hidden');
            
            // Limpiar formulario (opcional)
            // clearLogForm(parent, type);
        } else {
            throw new Error(result.data?.message || 'Error desconocido');
        }
    } catch (error) {
        console.error('Error al guardar log:', error);
        showNotification(`Error al guardar: ${error.message}`, 'error');
    }
}

/**
 * Marca un día como completado visualmente
 */
function markDayAsComplete(week, type) {
    // Buscar la tarjeta del día actual
    const dayCards = document.querySelectorAll(
        `.day-card[data-week="${week}"][data-type="${type}"][data-day="${KF_STATE.activeDay}"]`
    );
    
    dayCards.forEach(card => {
        card.classList.add('is-done');
        
        const checkIcon = card.querySelector('.check-icon');
        if (checkIcon) {
            checkIcon.innerHTML = '✔';
        }
    });
}

/**
 * Quita el estado de completado del día ACTIVO
 */
async function uncheckActiveDay(type, button) {
    if (!KF_STATE.activeDay) return;
    
    if (!confirm('¿Quieres marcar este día como no completado?')) return;
    
    const formData = new FormData();
    formData.append('action', 'kf_uncheck_day');
    formData.append('post_id', KF_STATE.postId);
    formData.append('week', KF_STATE.activeWeek);
    formData.append('day', KF_STATE.activeDay);
    formData.append('type', type);
    
    try {
        const response = await fetch(KF_STATE.ajaxUrl, {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Quitar estado visual de la tarjeta
            const prefix = type === 'ent' ? 'ent' : 'nut';
            const card = document.querySelector(`.day-card[data-week="${KF_STATE.activeWeek}"][data-type="${type}"][data-day="${KF_STATE.activeDay}"]`);
            
            if (card) {
                card.classList.remove('is-done');
                const checkIcon = card.querySelector('.check-icon');
                if (checkIcon) checkIcon.innerHTML = '';
            }
            
            // Ocultar este botón
            button.classList.add('is-hidden');
            
            showNotification('Día desmarcado correctamente', 'success');
        } else {
            throw new Error(result.data?.message || 'Error al desmarcar');
        }
    } catch (error) {
        console.error('Error al desmarcar día:', error);
        showNotification(error.message, 'error');
    }
}

// ============================================================================
// GUARDADO DEL PLAN COMPLETO (COACH)
// ============================================================================

/**
 * Guarda todo el plan (solo coaches)
 */
async function savePlan() {
    if (!KF_STATE.isCoach) {
        showNotification('No tienes permisos para guardar el plan', 'error');
        return;
    }
    
    const btn = document.querySelector('.btn-save-all');
    const status = document.getElementById('saveStatus');
    
    // Activar estado de guardando
    btn.classList.add('saving');
    btn.disabled = true;
    btn.innerHTML = `
        <span class="btn-icon">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <circle cx="12" cy="12" r="10"></circle>
            </svg>
        </span>
        <span class="btn-text">Guardando...</span>
        <span class="btn-shine"></span>
    `;
    
    try {
        // Recopilar todos los datos de las tarjetas
        const formContent = document.getElementById('form-content');
        formContent.innerHTML = '';
        
        document.querySelectorAll('.day-card').forEach(card => {
            const type = card.dataset.type === 'ent' ? 'entrenamiento' : 'nutricion';
            const week = card.dataset.week;
            const day = card.dataset.day;
            
            const titleEl = card.querySelector('[data-field="t"]');
            const descEl = card.querySelector('[data-field="d"]');
            
            if (titleEl && descEl) {
                const title = titleEl.innerText.trim();
                const description = descEl.innerHTML;
                
                formContent.innerHTML += `
                    <input type="hidden" name="${type}[${week}][${day}][t]" value="${escapeHtml(title)}">
                `;
                formContent.innerHTML += `
                    <textarea name="${type}[${week}][${day}][d]" style="display:none">${description}</textarea>
                `;
            }
        });
        
        // Agregar consejos del coach
        const consejosArea = document.getElementById('consejos-area');
        if (consejosArea) {
            formContent.innerHTML += `
                <textarea name="consejos_coach" style="display:none">${consejosArea.innerHTML}</textarea>
            `;
        }
        
        // Enviar formulario
        document.getElementById('form-hidden').submit();
        
        // Nota: El feedback se maneja con el parámetro ?success=1 en la URL después del redirect
        
    } catch (error) {
        console.error('Error al guardar plan:', error);
        
        // Restaurar botón
        restoreSaveButton(btn);
        
        // Mostrar error
        showSaveError('Error al guardar el plan');
    }
}

/**
 * Restaura el botón de guardar a su estado original
 */
function restoreSaveButton(btn) {
    btn.classList.remove('saving');
    btn.disabled = false;
    btn.innerHTML = `
        <span class="btn-icon">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                <polyline points="17 21 17 13 7 13 7 21"></polyline>
                <polyline points="7 3 7 8 15 8"></polyline>
            </svg>
        </span>
        <span class="btn-text">Guardar Todo el Plan</span>
        <span class="btn-shine"></span>
    `;
}

function showSaveError(message) {
    const status = document.getElementById('saveStatus');
    if (!status) return;
    
    status.classList.add('error');
    status.innerHTML = `
        <svg class="status-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
        </svg>
        <span class="status-text">${message || 'Error al guardar'}</span>
    `;
    status.classList.add('show');
    
    setTimeout(() => {
        status.classList.remove('show');
        status.classList.remove('error');
    }, 4000);
}

// ============================================================================
// SISTEMA DE CHAT
// ============================================================================

/**
 * Inicializa el sistema de chat
 */
function initChat() {
    const chatForm = document.getElementById('chat-form');
    if (!chatForm) return;
    
    chatForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const input = document.getElementById('chat-input');
        const box = document.getElementById('chat-box');
        const sendBtn = chatForm.querySelector('.send-btn');
        
        const message = input.value.trim();
        if (!message) return;
        
        // Deshabilitar input mientras se envía
        input.disabled = true;
        sendBtn.disabled = true;
        
        try {
            // Agregar mensaje al chat inmediatamente (optimistic UI)
            const tempMsg = createMessageElement(message, 'me', true);
            box.appendChild(tempMsg);
            box.scrollTop = box.scrollHeight;
            
            // Limpiar input
            input.value = '';
            
            // Enviar al servidor
            const formData = new FormData();
            formData.append('action', 'kf_send_chat_msg');
            formData.append('msg', message);
            formData.append('post_id', KF_STATE.postId);
            
            const response = await fetch(KF_STATE.ajaxUrl, {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (!result.success) {
                throw new Error('Error al enviar mensaje');
            }
            
            // Mensaje enviado correctamente
            tempMsg.classList.remove('sending');
            
        } catch (error) {
            console.error('Error al enviar mensaje:', error);
            showNotification('Error al enviar mensaje', 'error');
        } finally {
            // Rehabilitar input
            input.disabled = false;
            sendBtn.disabled = false;
            input.focus();
        }
    });
}

/**
 * Crea un elemento de mensaje para el chat
 */
function createMessageElement(text, type = 'me', isSending = false) {
    const wrapper = document.createElement('div');
    wrapper.className = `msg-wrapper ${type} ${isSending ? 'sending' : ''}`;
    
    const currentUser = '<?php echo esc_js(wp_get_current_user()->display_name); ?>';
    const isCoach = <?php echo $es_coach ? 'true' : 'false'; ?>;
    
    wrapper.innerHTML = `
        <div class="msg">
            <div class="msg-header">
                <span class="msg-author">
                    ${isCoach && type === 'me' ? '<span class="coach-badge">🏅</span> COACH' : currentUser}
                </span>
                <span class="msg-time">Ahora</span>
            </div>
            <div class="msg-content">${escapeHtml(text)}</div>
        </div>
    `;
    
    return wrapper;
}

// ============================================================================
// UTILIDADES
// ============================================================================

/**
 * Muestra notificación toast
 */
function showNotification(message, type = 'info') {
    // Crear elemento de notificación
    const notification = document.createElement('div');
    notification.className = `kf-notification kf-notification-${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <span class="notification-icon">${getNotificationIcon(type)}</span>
            <span class="notification-text">${message}</span>
        </div>
    `;
    
    // Agregar estilos si no existen
    if (!document.getElementById('kf-notification-styles')) {
        const styles = document.createElement('style');
        styles.id = 'kf-notification-styles';
        styles.textContent = `
            .kf-notification {
                position: fixed;
                top: 20px;
                right: 20px;
                background: #1a1a1a;
                color: white;
                padding: 16px 24px;
                border-radius: 12px;
                box-shadow: 0 4px 20px rgba(0,0,0,0.3);
                z-index: 10000;
                opacity: 0;
                transform: translateX(100%);
                transition: all 0.3s ease;
                border-left: 4px solid #666;
            }
            .kf-notification.show {
                opacity: 1;
                transform: translateX(0);
            }
            .kf-notification-success { border-left-color: #10b981; }
            .kf-notification-error { border-left-color: #ef4444; }
            .kf-notification-warning { border-left-color: #f59e0b; }
            .kf-notification-info { border-left-color: #3b82f6; }
            .notification-content {
                display: flex;
                align-items: center;
                gap: 12px;
            }
            .notification-icon { font-size: 20px; }
            .notification-text { font-size: 14px; font-weight: 500; }
        `;
        document.head.appendChild(styles);
    }
    
    // Agregar al DOM
    document.body.appendChild(notification);
    
    // Animar entrada
    setTimeout(() => notification.classList.add('show'), 10);
    
    // Remover después de 4 segundos
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => notification.remove(), 300);
    }, 4000);
}

/**
 * Obtiene el icono según el tipo de notificación
 */
function getNotificationIcon(type) {
    const icons = {
        success: '✓',
        error: '✕',
        warning: '⚠',
        info: 'ℹ'
    };
    return icons[type] || icons.info;
}

/**
 * Escapa HTML para prevenir XSS
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

/**
 * Detecta cambios no guardados
 */
function setupUnsavedChangesWarning() {
    if (!KF_STATE.isCoach) return;
    
    // Detectar cambios en campos editables
    document.querySelectorAll('[contenteditable="true"]').forEach(el => {
        el.addEventListener('input', () => {
            KF_STATE.unsavedChanges = true;
        });
    });
    
    // Advertir antes de salir si hay cambios no guardados
    window.addEventListener('beforeunload', (e) => {
        if (KF_STATE.unsavedChanges) {
            e.preventDefault();
            e.returnValue = 'Tienes cambios sin guardar. ¿Estás seguro de salir?';
            return e.returnValue;
        }
    });
    
    // Resetear flag al guardar
    const saveBtn = document.querySelector('.btn-save-all');
    if (saveBtn) {
        saveBtn.addEventListener('click', () => {
            KF_STATE.unsavedChanges = false;
        });
    }
}

// ============================================================================
// INICIALIZACIÓN
// ============================================================================

/**
 * Inicializa toda la aplicación
 */
function initApp() {
    console.log('🚀 KrysionFit - Iniciando aplicación...');
    
    // Verificar notificación de éxito en URL
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('success') === '1') {
        showNotification('¡Plan guardado exitosamente!', 'success');
        
        // Limpiar URL
        window.history.replaceState({}, document.title, window.location.pathname);
    }
    
    // Inicializar componentes
    initChat();
    setupUnsavedChangesWarning();
    setupOutsideClick();
    
    // Renderizar vista inicial
    renderView();
    
    console.log('✅ Aplicación iniciada correctamente');
}

/**
 * Configura el cierre de seguimiento al hacer click afuera
 */
function setupOutsideClick() {
    document.addEventListener('click', (e) => {
        // Si no hay día activo, no hacemos nada
        if (!KF_STATE.activeDay) return;
        
        // Verificar si el click fue dentro de una tarjeta de día o del contenedor de log
        const isClickInsideDay = e.target.closest('.day-card');
        const isClickInsideLog = e.target.closest('.kf-log-container');
        const isNavBtn = e.target.closest('.nav-btn') || e.target.closest('.week-btn');
        
        if (!isClickInsideDay && !isClickInsideLog && !isNavBtn) {
            hideTracking();
        }
    });
}

// Inicializar cuando el DOM esté listo
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initApp);
} else {
    initApp();
}

/**
 * Función para guardar datos hacia ACF
 */
function guardarHaciaACF() {
    if (!KF_STATE.isCoach) {
        alert('Solo los coaches pueden guardar datos.');
        return;
    }
    
    const btn = document.querySelector('.btn-sync-acf');
    if (btn) {
        btn.textContent = '⏳ Guardando...';
        btn.disabled = true;
    }
    
    // Recolectar todos los datos de la plantilla
    const planData = {};
    const diasSlugs = ["lunes", "martes", "miercoles", "jueves", "viernes", "sabado", "domingo"];
    
    // Recorrer todas las semanas
    for (let w = 1; w <= KF_STATE.totalWeeks; w++) {
        const weekData = {};
        const nutData = {};
        
        // Recorrer todos los días de la semana
        diasSlugs.forEach(slug => {
            // Buscar las tarjetas de entrenamiento
            const entCard = document.querySelector(`[data-type="ent"][data-week="${w}"][data-day="${slug}"]`);
            if (entCard) {
                const titleEl = entCard.querySelector('.edit-t[data-field="t"]');
                const descEl = entCard.querySelector('.edit-d[data-field="d"]');
                
                if (titleEl) {
                    const titleValue = titleEl.innerText || titleEl.textContent || 'Descanso';
                    weekData[slug] = titleValue;
                    console.log(`Found ${slug} title:`, titleValue);
                }
                if (descEl) {
                    const descValue = descEl.innerHTML || '';
                    weekData['descripcion_' + slug] = descValue;
                    console.log(`Found ${slug} desc:`, descValue);
                }
            } else {
                console.log(`No ent card found for week ${w}, day ${slug}`);
            }
            
            // Buscar las tarjetas de nutrición
            const nutCard = document.querySelector(`[data-type="nut"][data-week="${w}"][data-day="${slug}"]`);
            if (nutCard) {
                const titleEl = nutCard.querySelector('.edit-t[data-field="t"]');
                const descEl = nutCard.querySelector('.edit-d[data-field="d"]');
                
                if (titleEl) {
                    const titleValue = titleEl.innerText || titleEl.textContent || 'Plan Dieta';
                    nutData[slug] = titleValue;
                    console.log(`Found nut ${slug} title:`, titleValue);
                }
                if (descEl) {
                    const descValue = descEl.innerHTML || '';
                    nutData['descripcion_' + slug] = descValue;
                    console.log(`Found nut ${slug} desc:`, descValue);
                }
            } else {
                console.log(`No nut card found for week ${w}, day ${slug}`);
            }
        });
        
        planData[`semana_${w}`] = weekData;
        planData[`nutricion_${w}`] = nutData;
    }
    
    console.log('Complete plan data to send:', planData);
    
    // Obtener consejos del coach
    const consejosEl = document.querySelector('#consejos-coach');
    if (consejosEl) {
        planData.consejos_coach = consejosEl.innerHTML || '';
    }
    
    // Realizar petición AJAX para guardar hacia ACF
    fetch(KF_STATE.ajaxUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            action: 'kf_save_to_acf',
            post_id: KF_STATE.postId,
            plan_data: JSON.stringify(planData),
            nonce: '<?php echo wp_create_nonce('kf_save_acf'); ?>'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Datos guardados correctamente en ACF');
        } else {
            alert('Error al guardar: ' + (data.message || 'Error desconocido'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error de conexión al guardar');
    })
    .finally(() => {
        if (btn) {
            btn.textContent = '💾 Guardar en ACF';
            btn.disabled = false;
        }
    });
}
</script>


<!-- PDF HIDDEN TEMPLATE -->
<div id="pdf-hidden-container">
    <div id="pdf-content">
        <!-- Contenido dinámico via JS -->
    </div>
</div>

<?php get_footer(); ?>