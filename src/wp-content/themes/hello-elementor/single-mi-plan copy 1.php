<?php
/**
 * Template Name: KrysionFit - Gestión Elite
 */
// Configuración global del plan
$total_semanas = 5; // Cambia esto y todo el sistema se adapta solo
// 1. SEGURIDAD Y VALIDACIÓN DE ACCESO
$post_id = get_the_ID();
$current_user_id = get_current_user_id();
$es_coach = current_user_can('administrator');

$cliente_asignado = get_field('cliente', $post_id);
$id_cliente_plan = 0;
if (is_numeric($cliente_asignado)) { $id_cliente_plan = $cliente_asignado; } 
elseif (is_array($cliente_asignado) && isset($cliente_asignado['ID'])) { $id_cliente_plan = $cliente_asignado['ID']; } 
elseif (is_object($cliente_asignado) && isset($cliente_asignado->ID)) { $id_cliente_plan = $cliente_asignado->ID; }

if (!is_user_logged_in()) { auth_redirect(); exit; }
if (!$es_coach && (int)$id_cliente_plan !== (int)$current_user_id) { wp_redirect(home_url()); exit; }

// 2. LÓGICA DE GESTIÓN (Mantenida igual)
if ( $es_coach ) {
    if ( isset($_POST['action']) && $_POST['action'] == 'kf_create_new_plan' ) {
        $user_id = intval($_POST['user_id']);
        $objetivo = sanitize_text_field($_POST['objetivo']);
        $nivel = sanitize_text_field($_POST['nivel']);
        $user_info = get_userdata($user_id);
        if ($user_info) {
            $new_plan_id = wp_insert_post([
                'post_title'   => 'PLAN DE ' . strtoupper($user_info->display_name),
                'post_type'    => 'mi-plan',
                'post_status'  => 'publish',
            ]);
            if($new_plan_id) {
                update_field('cliente', $user_id, $new_plan_id); 
                update_field('objetivo_atleta', $objetivo, $new_plan_id); 
                update_field('nivel_atleta', $nivel, $new_plan_id);
                wp_redirect(get_permalink($new_plan_id) . "?success=created");
                exit;
            }
        }
    }
    if ( isset($_POST['action']) && $_POST['action'] == 'kf_save_full_plan' ) {
        $dias_slugs = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo'];
        for ($w = 1; $w <= $total_semanas; $w++) {
            if (isset($_POST['entrenamiento'][$w])) {
                $data_ent = [];
                foreach ($dias_slugs as $slug) {
                    $data_ent[$slug] = sanitize_text_field($_POST['entrenamiento'][$w][$slug]['t']);
                    $data_ent['descripcion_' . $slug] = wp_kses_post($_POST['entrenamiento'][$w][$slug]['d']);
                }
                update_field("semana_$w", $data_ent, $post_id);
            }
            if (isset($_POST['nutricion'][$w])) {
                $data_nut = [];
                foreach ($dias_slugs as $slug) {
                    $data_nut[$slug] = sanitize_text_field($_POST['nutricion'][$w][$slug]['t']);
                    $data_nut['descripcion_' . $slug] = wp_kses_post($_POST['nutricion'][$w][$slug]['d']);
                }
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

<style>
  :root { --kf-orange: #F2600C; --kf-bg: #0D0D0D; --kf-card: #141414; --kf-line: #262626; --kf-text: #efefef; --kf-gray: #888; }
  
  /* FORZAR ESTRUCTURA DE PÁGINA CORRECTA */
  html, body {
      height: auto !important;
      min-height: 100vh !important;
  }
  
  body {
      display: flex !important;
      flex-direction: column !important;
  }
  
  /* FORZAR FOOTER AL FINAL COMPLETAMENTE */
  body > footer,
  body > .site-footer,
  #colophon,
  .site-footer,
  footer.footer,
  footer[role="contentinfo"] {
      position: static !important;
      bottom: auto !important;
      left: auto !important;
      right: auto !important;
      top: auto !important;
      transform: none !important;
      margin-top: auto !important;
      width: 100% !important;
      z-index: 1 !important;
  }
  
  /* Asegurar que el contenido principal empuje el footer */
  main,
  #main,
  .site-content,
  #content {
      flex: 1 0 auto !important;
  }
  
  /* REPARACIÓN DEL FOOTER - AUMENTADO EL PADDING */
  .kf-wrapper-full { 
      background: var(--kf-bg); 
      width: 100%; 
      display: block; 
      clear: both; 
      overflow: visible;
      padding: 60px 0 120px 0;
      min-height: 100vh;
      position: relative;
      flex: 1 0 auto;
  }

  .kf-container { max-width: 1200px; margin: 0 auto; padding: 0 20px; }

  /* Elementos de la Interfaz */
  .kf-admin-bar { background: #000; border-left: 4px solid var(--kf-orange); padding: 20px 30px; border-radius: 12px; margin-bottom: 40px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 10px 30px rgba(0,0,0,0.5); }
  
  .kf-plan-header { text-align: center; margin-bottom: 40px; }
  .kf-plan-title { font-size: clamp(24px, 5vw, 42px); font-weight: 800; color: #fff; text-transform: uppercase; margin: 0; }
  .kf-plan-title span { color: var(--kf-orange); }

  /* TABS Y NAVEGACIÓN */
  .kf-nav-group { margin-bottom: 30px; text-align: center; }
  .kf-nav-main { display: flex; justify-content: center; gap: 12px; margin-bottom: 15px; flex-wrap: wrap; }
  .nav-btn { background: var(--kf-card); border: 1px solid var(--kf-line); color: var(--kf-gray); padding: 12px 30px; border-radius: 50px; cursor: pointer; font-weight: 700; transition: 0.3s; }
  .nav-btn.active { background: var(--kf-orange); color: white; border-color: var(--kf-orange); }
  
  .kf-nav-weeks { display: flex; justify-content: center; gap: 8px; flex-wrap: wrap; }
  .week-btn { background: var(--kf-card); border: 1px solid var(--kf-line); color: var(--kf-gray); padding: 8px 18px; border-radius: 50px; cursor: pointer; font-size: 12px; font-weight: 600; transition: 0.3s; }
  .week-btn.active { color: var(--kf-orange); border-color: var(--kf-orange); }

  /* GRID PRINCIPAL */
  .kf-main-grid { display: grid; grid-template-columns: 1.6fr 0.8fr; gap: 25px; align-items: start; }
  @media (max-width: 992px) { .kf-main-grid { grid-template-columns: 1fr; } }

  .day-card { background: var(--kf-card); border: 1px solid var(--kf-line); padding: 20px; border-radius: 15px; border-top: 4px solid var(--kf-orange); margin-bottom: 15px; transition: 0.3s; }
  .day-card:hover { border-color: var(--kf-orange); }
  .day-label { font-size: 11px; font-weight: 800; color: var(--kf-orange); text-transform: uppercase; margin-bottom: 10px; }
  .edit-t { font-size: 16px; font-weight: 700; color: #fff; margin-bottom: 5px; outline: none; }
  .edit-d { font-size: 13px; color: #bbb; line-height: 1.6; outline: none; min-height: 20px; word-wrap: break-word; overflow-wrap: break-word; word-break: break-word; white-space: normal;
    }
    
    /* Eliminar los márgenes del reset.css que causan saltos grandes */
    .edit-d p, .edit-d div {
        margin: 0 !important;
        padding: 0 !important;
        display: block; 
    }
    
    /* Si quieres que los párrafos tengan un pequeño espacio pero no exagerado */
    .edit-d p {
        margin-bottom: 4px !important; 
    }

  /* CHAT */
  .chat-container { 
    background: var(--kf-card); 
    border: 1px solid var(--kf-line); 
    border-radius: 15px; 
    display: flex; 
    flex-direction: column; 
    height: 500px; 
    position: block; 
    top: 20px;
    align-self: flex-start;
  }
  .chat-messages { flex-grow: 1; overflow-y: auto; padding: 15px; background: #0a0a0a; display: flex; flex-direction: column; gap: 10px; }
  .msg { max-width: 85%; padding: 12px; border-radius: 15px; font-size: 13px; }
  .msg.me { align-self: flex-end; background: var(--kf-orange); color: white; }
  .msg.other { align-self: flex-start; background: #262626; color: #ddd; }
  .chat-input-area { padding: 15px; display: flex; gap: 10px; border-top: 1px solid var(--kf-line); }
  .chat-input { flex: 1; background: #000; border: 1px solid var(--kf-line); color: white; padding: 10px; border-radius: 8px; }

  /* BOTÓN FLOTANTE */
  .admin-float { 
      position: fixed; 
      bottom: 30px; 
      left: 50%; 
      transform: translateX(-50%); 
      z-index: 999999;
      filter: drop-shadow(0 10px 30px rgba(242, 96, 12, 0.5));
  }
  .btn-save-all { 
      background: var(--kf-orange); 
      color: white; 
      border: none; 
      padding: 18px 60px; 
      border-radius: 100px; 
      font-weight: 800; 
      cursor: pointer; 
      font-size: 16px; 
      text-transform: uppercase; 
      transition: 0.3s; 
      box-shadow: 0 4px 15px rgba(0,0,0,0.3);
  }
  .btn-save-all:hover { 
      transform: scale(1.05); 
      background: #ff711f; 
      box-shadow: 0 6px 25px rgba(242, 96, 12, 0.6);
  }

  /* NOTIFICACIÓN DE ÉXITO */
  .success-notification {
      position: fixed;
      top: 30px;
      left: 50%;
      transform: translateX(-50%) translateY(-150px);
      background: linear-gradient(135deg, #10b981 0%, #059669 100%);
      color: white;
      padding: 20px 40px;
      border-radius: 15px;
      font-weight: 700;
      font-size: 16px;
      box-shadow: 0 10px 40px rgba(16, 185, 129, 0.4);
      z-index: 1000000;
      display: flex;
      align-items: center;
      gap: 12px;
      opacity: 0;
      transition: all 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55);
  }
  
  .success-notification.show {
      transform: translateX(-50%) translateY(0);
      opacity: 1;
  }
  
  .success-notification .icon {
      font-size: 24px;
      animation: checkmark 0.6s ease-in-out;
  }
  
  @keyframes checkmark {
      0% { transform: scale(0) rotate(-45deg); }
      50% { transform: scale(1.2) rotate(10deg); }
      100% { transform: scale(1) rotate(0deg); }
  }
  
  .success-notification .progress-bar {
      position: absolute;
      bottom: 0;
      left: 0;
      height: 4px;
      background: rgba(255, 255, 255, 0.3);
      border-radius: 0 0 15px 15px;
      animation: progress 3s linear;
  }
  
  @keyframes progress {
      from { width: 100%; }
      to { width: 0%; }
  }

  .is-hidden { display: none !important; }

  /* RESPONSIVE */
  @media (max-width: 768px) {
      .kf-wrapper-full { padding: 40px 0 200px 0; }
      .kf-admin-bar { flex-direction: column; gap: 15px; text-align: center; }
      .admin-float { bottom: 20px; }
      .btn-save-all { padding: 15px 40px; font-size: 14px; }
      .success-notification { 
          top: 20px;
          padding: 16px 30px; 
          font-size: 14px; 
          max-width: 90%;
      }
      
      .kf-col-right {
        position: relative;
        top: auto;
        max-height: none;
      }
  }
  
  @media (max-width: 992px) { 
    .kf-main-grid { grid-template-columns: 1fr; }
    
    .kf-col-right {
      position: relative;
      top: auto;
      max-height: none;
    }
  }
</style>

<div class="kf-wrapper-full">
  <div class="kf-container">

    <?php if($es_coach): ?>
    <div class="kf-admin-bar">
        <div>
            <span style="font-weight:800; font-size:10px; color:var(--kf-orange);">MODO EDICIÓN</span>
            <h3 style="margin:0; font-size:16px; color:#fff;">Gestión del Plan</h3>
        </div>
        <div style="font-size:12px; color:var(--kf-gray);">Los cambios se guardan al pulsar el botón inferior</div>
    </div>
    <?php endif; ?>

    <header class="kf-plan-header">
        <h1 class="kf-plan-title">
            <?php 
                $words = explode(' ', $titulo_plan);
                if (count($words) > 1) {
                    $last_word = array_pop($words);
                    echo implode(' ', $words) . ' <span>' . $last_word . '</span>';
                } else {
                    echo '<span>' . $titulo_plan . '</span>';
                }
            ?>
        </h1>
        <span style="color:var(--kf-gray); font-size:12px;">Atleta: <?php echo esc_html(get_userdata($id_cliente_plan)->display_name); ?></span>
    </header>

    <div class="kf-nav-group">
        <div class="kf-nav-main">
            <button class="nav-btn active" id="btn-ent" onclick="switchMod('entrenamiento')">🏋️ ENTRENAMIENTO</button>
            <button class="nav-btn" id="btn-nut" onclick="switchMod('nutricion')">🥗 NUTRICIÓN</button>
        </div>
        <div class="kf-nav-weeks">
            <?php for($i=1; $i<=5; $i++): ?>
                <button class="week-btn <?php echo ($i==1)?'active':''; ?>" data-week="<?php echo $i; ?>" onclick="switchWeek(<?php echo $i; ?>)">Semana <?php echo $i; ?></button>
            <?php endfor; ?>
        </div>
    </div>

    <div class="kf-main-grid">
      <div class="kf-col-left">
        <div id="content-area">
            <?php for ($w = 1; $w <= $total_semanas; $w++): ?>
                <div id="week-ent-<?php echo $w; ?>" class="mod-content mod-ent <?php echo ($w > 1) ? 'is-hidden' : ''; ?>">
                    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap:15px;">
                        <?php 
                        $plan = get_field("semana_$w", $post_id);
                        foreach($slugs_dias as $idx => $slug): ?>
                            <div class="day-card" data-type="ent" data-week="<?php echo $w; ?>" data-day="<?php echo $slug; ?>">
                                <div class="day-label"><?php echo $nombres_dias[$idx]; ?></div>
                                <div class="edit-t" contenteditable="<?php echo $es_coach?'true':'false'; ?>" data-field="t"><?php echo esc_html($plan[$slug] ?: 'Descanso'); ?></div>
                                <div class="edit-d" contenteditable="<?php echo $es_coach?'true':'false'; ?>" data-field="d"><?php echo wp_kses_post($plan['descripcion_'.$slug] ?: ''); ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div id="week-nut-<?php echo $w; ?>" class="mod-content mod-nut is-hidden">
                    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap:15px;">
                        <?php 
                        $plan_nut = get_field("nutricion_$w", $post_id);
                        foreach($slugs_dias as $idx => $slug): ?>
                            <div class="day-card" data-type="nut" data-week="<?php echo $w; ?>" data-day="<?php echo $slug; ?>">
                                <div class="day-label"><?php echo $nombres_dias[$idx]; ?></div>
                                <div class="edit-t" contenteditable="<?php echo $es_coach?'true':'false'; ?>" data-field="t"><?php echo esc_html($plan_nut[$slug] ?: 'Plan Dieta'); ?></div>
                                <div class="edit-d" contenteditable="<?php echo $es_coach?'true':'false'; ?>" data-field="d"><?php echo wp_kses_post($plan_nut['descripcion_'.$slug] ?: ''); ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endfor; ?>
        </div>
      </div>

      <div class="kf-col-right" style="position: sticky; top: 20px; align-self: flex-start; height: fit-content;">
        <div class="chat-container">
          <div style="padding:15px; border-bottom:1px solid var(--kf-line); font-size:12px; font-weight:700; color:var(--kf-orange);">💬 FEEDBACK DIRECTO</div>
          <div class="chat-messages" id="chat-box">
            <?php $msgs = get_comments(['post_id' => $post_id, 'order' => 'ASC']); foreach($msgs as $m): ?>
              <div class="msg <?php echo ($m->user_id == $current_user_id) ? 'me' : 'other'; ?>">
                <small style="display:block; font-size:9px; opacity:0.7; font-weight:700;">
                    <?php echo user_can($m->user_id, 'administrator') ? 'COACH' : esc_html($m->comment_author); ?>
                </small>
                <?php echo esc_html($m->comment_content); ?>
              </div>
            <?php endforeach; ?>
          </div>
          <form class="chat-input-area" id="chat-form">
            <input type="text" id="chat-input" class="chat-input" placeholder="Escribe al atleta..." required>
            <button type="submit" style="background:var(--kf-orange); border:none; color:white; width:40px; height:40px; border-radius:50%; cursor:pointer;">⚡</button>
          </form>
        </div>
        
        <div style="margin-top:20px; background:var(--kf-card); padding:20px; border-radius:15px; border:1px solid var(--kf-line);">
          <h4 style="font-size:12px; color:var(--kf-orange); margin:0 0 10px 0;">🧠 NOTAS PRIVADAS / CONSEJOS</h4>
          <div id="consejos-area" contenteditable="<?php echo $es_coach?'true':'false'; ?>" style="font-size:13px; color:#ccc; min-height:80px; outline:none;">
            <?php echo wp_kses_post($consejos_html); ?>
          </div>
        </div>
      </div>
    </div>

    <?php if($es_coach): ?>
    <div class="admin-float">
      <button class="btn-save-all" onclick="savePlan()">Guardar Todo el Plan</button>
    </div>
    <form id="form-hidden" method="POST" style="display:none;">
      <input type="hidden" name="action" value="kf_save_full_plan">
      <div id="form-content"></div>
    </form>
    <?php endif; ?>
    
  </div>
</div>

<script>
let activeMod = 'entrenamiento';
let activeWeek = 1;

function switchMod(mod) {
    activeMod = mod;
    document.querySelectorAll('.nav-btn').forEach(b => b.classList.remove('active'));
    document.getElementById(mod === 'entrenamiento' ? 'btn-ent' : 'btn-nut').classList.add('active');
    renderView();
}

function switchWeek(week) {
    activeWeek = week;
    document.querySelectorAll('.week-btn').forEach(b => {
        b.classList.remove('active');
        if(parseInt(b.dataset.week) === week) b.classList.add('active');
    });
    renderView();
}

function renderView() {
    document.querySelectorAll('.mod-content').forEach(c => c.classList.add('is-hidden'));
    const targetId = (activeMod === 'entrenamiento') ? `week-ent-${activeWeek}` : `week-nut-${activeWeek}`;
    document.getElementById(targetId).classList.remove('is-hidden');
}

function showSuccessNotification() {
    // Crear notificación si no existe
    let notification = document.getElementById('success-notification');
    if (!notification) {
        notification = document.createElement('div');
        notification.id = 'success-notification';
        notification.className = 'success-notification';
        notification.innerHTML = `
            <span class="icon">✓</span>
            <span>¡Plan guardado exitosamente!</span>
            <div class="progress-bar"></div>
        `;
        document.body.appendChild(notification);
    }
    
    // Mostrar notificación
    setTimeout(() => notification.classList.add('show'), 100);
    
    // Ocultar después de 3 segundos
    setTimeout(() => {
        notification.classList.remove('show');
    }, 3000);
}

function savePlan() {
    const content = document.getElementById('form-content');
    content.innerHTML = '';
    document.querySelectorAll('.day-card').forEach(card => {
        const type = card.dataset.type === 'ent' ? 'entrenamiento' : 'nutricion';
        const w = card.dataset.week;
        const d = card.dataset.day;
        const t = card.querySelector('[data-field="t"]').innerText;
        const desc = card.querySelector('[data-field="d"]').innerHTML;
        content.innerHTML += `<input type="hidden" name="${type}[${w}][${d}][t]" value="${t}">`;
        content.innerHTML += `<textarea name="${type}[${w}][${d}][d]" style="display:none">${desc}</textarea>`;
    });
    content.innerHTML += `<textarea name="consejos_coach" style="display:none">${document.getElementById('consejos-area').innerHTML}</textarea>`;
    document.getElementById('form-hidden').submit();
}

// Mostrar notificación si hay parámetro success en la URL
window.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('success') === '1') {
        showSuccessNotification();
        // Limpiar el parámetro de la URL sin recargar la página
        const cleanUrl = window.location.pathname;
        window.history.replaceState({}, document.title, cleanUrl);
    }
});

// Chat AJAX
document.getElementById('chat-form').addEventListener('submit', function(e) {
    e.preventDefault();
    const input = document.getElementById('chat-input'), box = document.getElementById('chat-box');
    const msg = input.value;
    if(!msg) return;
    box.innerHTML += `<div class="msg me"><small style="display:block; font-size:9px; font-weight:700;">Tú</small>${msg}</div>`;
    box.scrollTop = box.scrollHeight;
    input.value = '';
    const data = new FormData();
    data.append('action', 'kf_send_chat_msg');
    data.append('msg', msg);
    data.append('post_id', '<?php echo $post_id; ?>');
    fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: data });
});


</script>

<?php get_footer(); ?>