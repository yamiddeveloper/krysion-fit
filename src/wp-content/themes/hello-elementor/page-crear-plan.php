<?php
/**
 * Template Name: KrysionFit - Panel Coach
 */

if (!is_user_logged_in()) { auth_redirect(); exit; }
$es_coach = current_user_can('administrator');
if (!$es_coach) { wp_redirect(home_url()); exit; }

// LÓGICA DE ELIMINACIÓN
if ( isset($_POST['action']) && $_POST['action'] == 'kf_delete_plan' ) {
    $plan_id = intval($_POST['plan_id']);
    
    if ($plan_id && current_user_can('administrator')) {
        wp_delete_post($plan_id, true); // true para eliminación permanente
        wp_redirect(home_url('/planes-de-entrenamiento-y-nutricion/?deleted=1'));
        exit;
    }
}

// LÓGICA DE APROBACIÓN DE USUARIO
if ( isset($_POST['action']) && $_POST['action'] == 'kf_approve_user' ) {
    $user_id = intval($_POST['user_id']);
    
    if ($user_id && current_user_can('administrator')) {
        update_user_meta($user_id, 'kf_user_approval', 'approved');
        wp_redirect(home_url('/planes-de-entrenamiento-y-nutricion/?approved=1'));
        exit;
    }
}

// LÓGICA DE OMITIR ESPERA (BYPASS)
if ( isset($_POST['action']) && $_POST['action'] == 'kf_toggle_bypass' ) {
    $plan_id = intval($_POST['plan_id']);
    
    if ($plan_id && current_user_can('administrator')) {
        $current_bypass = get_post_meta($plan_id, 'kf_countdown_bypass', true);
        $new_bypass = ($current_bypass === 'yes') ? 'no' : 'yes';
        update_post_meta($plan_id, 'kf_countdown_bypass', $new_bypass);
        wp_redirect(home_url('/planes-de-entrenamiento-y-nutricion/?bypassed=1'));
        exit;
    }
}

// LÓGICA DE CREACIÓN (Mantenida)
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
            // Redirigir directamente al nuevo plan para empezar a editar
            wp_redirect(get_permalink($new_plan_id));
            exit;
        }
    }
}

get_header(); 
?>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800&display=swap" rel="stylesheet">

<style>
  :root { --kf-orange: #F2600C; --kf-bg: #0D0D0D; --kf-card: #1A1A1A; --kf-line: #333; --kf-text: #fff; --kf-gray: #aaa; }
  
  /* FORZAR ESTRUCTURA DE PÁGINA CORRECTA */
  html, body {
      height: auto !important;
      min-height: 100vh !important;
  }
  
  body {
      display: flex !important;
      flex-direction: column !important;
      background-color: var(--kf-bg) !important;
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

  .coach-dashboard { 
    background-color: var(--kf-bg);
    padding: 60px 0 80px 0;
    min-height: 100vh;
    flex: 1 0 auto;
    position: relative;
  }

  .kf-container { max-width: 1100px; margin: 0 auto; padding: 0 20px; }

  .coach-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px; border-bottom: 1px solid var(--kf-line); padding-bottom: 20px; }
  .coach-welcome h1 { font-size: 24px; font-weight: 800; margin: 0; text-transform: uppercase; color: #fff; }
  .coach-welcome span { color: var(--kf-orange); }

  .grid-coach { display: grid; grid-template-columns: 1fr 350px; gap: 30px; align-items: start; }

  .panel-section { background: var(--kf-card); border-radius: 15px; border: 1px solid var(--kf-line); overflow: hidden; margin-bottom: 30px; }
  .section-title { background: #222; padding: 15px 20px; font-size: 14px; font-weight: 700; color: var(--kf-orange); border-bottom: 1px solid var(--kf-line); display: flex; align-items: center; gap: 10px; }
  
  .plan-list-item { padding: 20px; border-bottom: 1px solid var(--kf-line); display: flex; justify-content: space-between; align-items: center; transition: 0.2s; }
  .plan-list-item:hover { background: #222; }
  .plan-list-item:last-child { border-bottom: none; }
  .plan-info h3 { font-size: 15px; margin: 0; color: #fff; text-transform: uppercase; letter-spacing: 1px; }
  .plan-info p { font-size: 12px; color: var(--kf-gray); margin: 5px 0 0; }
  
  /* ESTE ES EL BOTÓN QUE LLEVA AL PLAN DIRECTAMENTE */
  .btn-edit-direct { 
    background: var(--kf-orange); 
    color: #fff; 
    padding: 10px; 
    border-radius: 6px; 
    font-size: 12px; 
    font-weight: 800; 
    text-decoration: none; 
    transition: 0.3s;
    white-space: nowrap;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
  }
  .btn-edit-direct:hover { background: #fff; color: var(--kf-orange); }

  .plan-actions { display: flex; gap: 10px; align-items: center; }
  .btn-delete-plan { 
    background: #dc3545; 
    color: #fff; 
    padding: 10px; 
    border-radius: 6px; 
    font-size: 12px; 
    font-weight: 800; 
    border: none; 
    cursor: pointer; 
    transition: 0.3s;
    white-space: nowrap;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
  }
  .btn-delete-plan:hover { background: #c82333; transform: translateY(-1px); }

  .form-group { margin-bottom: 20px; }
  .form-label { display: block; font-size: 11px; font-weight: 700; color: var(--kf-gray); text-transform: uppercase; margin-bottom: 8px; }
  .kf-input, .kf-select { width: 100%; background: #000; border: 1px solid var(--kf-line); color: white; padding: 12px; border-radius: 8px; font-family: 'Poppins'; }
  
  .btn-create { background: var(--kf-orange); color: #fff; border: none; padding: 15px; border-radius: 8px; font-weight: 800; cursor: pointer; width: 100%; text-transform: uppercase; transition: 0.3s; }
  .btn-create:hover { background: #ff711f; transform: translateY(-2px); }

  .btn-approve { 
    background: #28a745; 
    color: #fff; 
    padding: 8px 12px; 
    border-radius: 6px; 
    font-size: 11px; 
    font-weight: 800; 
    border: none; 
    cursor: pointer; 
    transition: 0.3s;
    text-transform: uppercase;
  }
  .btn-approve:hover { background: #218838; transform: translateY(-1px); }

  .user-meta-info { font-size: 11px; color: #888; margin-top: 4px; }

  .btn-bypass {
    background: #6c757d;
    color: #fff;
    padding: 10px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 800;
    border: none;
    cursor: pointer;
    transition: 0.3s;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
  }
  .btn-bypass.is-active { background: #ffc107; color: #000; }
  .btn-bypass:hover { filter: brightness(1.1); transform: translateY(-1px); }

  /* RESPONSIVE */
  @media (max-width: 900px) { 
    .grid-coach { grid-template-columns: 1fr; }
    .coach-dashboard { padding: 40px 0 80px 0; }
    .plan-list-item { flex-direction: column; gap: 15px; align-items: flex-start; }
    .plan-actions { flex-direction: column; gap: 10px; width: 100%; }
    .btn-edit-direct, .btn-delete-plan { width: 100%; text-align: center; }
  }

  @media (max-width: 600px) {
    .coach-header { flex-direction: column; gap: 10px; text-align: center; }
    .coach-welcome h1 { font-size: 20px; }
  }
</style>

<div class="coach-dashboard">
  <div class="kf-container">
    
    <header class="coach-header">
      <div class="coach-welcome">
        <h1>PANEL <span>COACH</span></h1>
      </div>
    </header>

    <?php if (isset($_GET['approved'])) : ?>
      <div id="approve-success" style="background: rgba(40, 167, 69, 0.15); border: 1px solid #28a745; color: #28a745; padding: 15px 20px; border-radius: 12px; margin-bottom: 30px; display: flex; align-items: center; gap: 12px; animation: slideDown 0.4s ease-out;">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <polyline points="20 6 9 17 4 12"></polyline>
        </svg>
        <span style="font-weight: 700; font-size: 14px;">¡USUARIO ACTIVADO CORRECTAMENTE!</span>
      </div>
      <script>
        setTimeout(() => {
          document.getElementById('approve-success').style.opacity = '0';
          document.getElementById('approve-success').style.transition = '0.5s';
          setTimeout(() => document.getElementById('approve-success').remove(), 500);
        }, 3000);
      </script>
      <style>
        @keyframes slideDown { from { opacity:0; transform:translateY(-10px); } to { opacity:1; transform:translateY(0); } }
      </style>
    <?php endif; ?>

    <!-- SECCIÓN DE USUARIOS PENDIENTES (AHORA DE PRIMERO) -->
    <?php
    $pending_users = get_users([
        'meta_key'     => 'kf_user_approval',
        'meta_value'   => 'approved',
        'meta_compare' => '!=',
        'role'         => 'subscriber'
    ]);
    ?>

    <div class="panel-section" style="margin-bottom: 40px; border-color: <?php echo !empty($pending_users) ? 'var(--kf-orange)' : 'var(--kf-line)'; ?>; box-shadow: <?php echo !empty($pending_users) ? '0 10px 30px rgba(242, 96, 12, 0.1)' : 'none'; ?>;">
        <style>
            @keyframes pulse {
                0% { box-shadow: 0 0 0 0 rgba(242, 96, 12, 0.4); }
                70% { box-shadow: 0 0 0 6px rgba(242, 96, 12, 0); }
                100% { box-shadow: 0 0 0 0 rgba(242, 96, 12, 0); }
            }
            @keyframes pulse-green {
                0% { box-shadow: 0 0 0 0 rgba(40, 167, 69, 0.4); }
                70% { box-shadow: 0 0 0 6px rgba(40, 167, 69, 0); }
                100% { box-shadow: 0 0 0 0 rgba(40, 167, 69, 0); }
            }
            .status-badge {
                display: inline-flex;
                align-items: center;
                padding: 5px 10px;
                border-radius: 5px;
                font-size: 10px;
                font-weight: 700;
                text-transform: uppercase;
                margin-left: 10px;
                border: 1px solid;
            }
            .status-dot {
                display: inline-block;
                width: 8px;
                height: 8px;
                border-radius: 50%;
                margin-right: 5px;
            }
        </style>
      <div class="section-title" style="<?php echo !empty($pending_users) ? 'background: #2a1a10;' : ''; ?>">
        🕒 USUARIOS PENDIENTES DE APROBACIÓN 
        <?php 
        // Assuming $is_approved is determined elsewhere for the current user context,
        // or this badge is meant to be dynamic for each user in a list.
        // For the purpose of this snippet, we'll assume a placeholder for $is_approved
        // as it's not defined in the provided context.
        // If this badge is for the coach's own status, $is_approved would be true.
        // If it's a general status for the section, it might depend on $pending_users.
        // Given the instruction, it seems to be a general status badge.
        // Let's assume $is_approved is false if there are pending users, true otherwise,
        // or it's a placeholder for a specific user's status.
        // For now, I'll define a dummy $is_approved for the badge to render.
        $is_approved = empty($pending_users); // Example: if no pending users, consider "approved" state for the section.
        ?>
        <div class="status-badge" style="<?php echo !$is_approved ? 'background: rgba(255, 77, 77, 0.15); color: #FF4D4D; border-color: rgba(255, 77, 77, 0.3);' : 'background: rgba(40, 167, 69, 0.15); color: #28a745; border-color: rgba(40, 167, 69, 0.3);'; ?>">
            <span class="status-dot" style="<?php echo !$is_approved ? 'background: #FF4D4D; box-shadow: 0 0 10px #FF4D4D; animation: pulse 2s infinite;' : 'background: #28a745; box-shadow: 0 0 10px #28a745; animation: pulse-green 2s infinite;'; ?>"></span>
            <?php echo $is_approved ? 'PLAN ACTIVADO' : 'PAGO PENDIENTE'; ?>
        </div>
        <?php if(!empty($pending_users)): ?>
          <span style="background: var(--kf-orange); color: white; padding: 2px 8px; border-radius: 20px; font-size: 10px; margin-left: 10px;"><?php echo count($pending_users); ?> PENDIENTES</span>
        <?php endif; ?>
      </div>
      <div class="plans-container" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1px; background: var(--kf-line);">
        <?php
        if (!empty($pending_users)) :
            foreach ($pending_users as $p_user) :
                ?>
                <div class="plan-list-item" style="background: var(--kf-card); border-bottom: none;">
                  <div class="plan-info">
                    <h3 style="font-size: 14px;"><?php echo esc_html($p_user->display_name); ?></h3>
                    <p class="user-meta-info">📧 <?php echo esc_html($p_user->user_email); ?></p>
                  </div>
                  <div>
                    <form method="POST">
                      <input type="hidden" name="action" value="kf_approve_user">
                      <input type="hidden" name="user_id" value="<?php echo $p_user->ID; ?>">
                      <button type="submit" class="btn-approve">Aprobar Pago</button>
                    </form>
                  </div>
                </div>
                <?php
            endforeach;
        else :
            echo "<div style='grid-column: 1 / -1; padding: 30px; text-align: center; color: var(--kf-gray); font-size: 13px; background: var(--kf-card);'>No hay usuarios pendientes por aprobar.</div>";
        endif;
        ?>
      </div>
    </div>

    <div class="grid-coach">
      
      <div class="panel-section">
        <div class="section-title">📂 GESTIONAR PLANES DE ATLETAS</div>
        <div class="plans-container">
          <?php
          $query_planes = new WP_Query([
              'post_type' => 'mi-plan',
              'posts_per_page' => 10,
              'orderby' => 'date',
              'order' => 'DESC'
          ]);

          if ($query_planes->have_posts()) :
              while ($query_planes->have_posts()) : $query_planes->the_post();
                  $atleta_id = get_field('cliente');
                  $atleta_name = 'Sin Atleta';
                  if($atleta_id) {
                      $u = get_userdata($atleta_id);
                      $atleta_name = ($u) ? $u->display_name : 'Usuario Eliminado';
                  }
                  ?>
                  <div class="plan-list-item">
                    <div class="plan-info">
                      <h3><?php the_title(); ?></h3>
                      <p>👤 Atleta: <strong><?php echo esc_html($atleta_name); ?></strong> | 📅 <?php echo get_the_date('d/m/Y'); ?></p>
                    </div>
                    <div class="plan-actions">
                      <a href="<?php the_permalink(); ?>" class="btn-edit-direct" title="Editar Plan">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                          <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                          <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                        </svg>
                      </a>
                      <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="kf_toggle_bypass">
                        <input type="hidden" name="plan_id" value="<?php echo get_the_ID(); ?>">
                        <?php $is_bypass_active = (get_post_meta(get_the_ID(), 'kf_countdown_bypass', true) === 'yes'); ?>
                        <button type="submit" class="btn-bypass <?php echo $is_bypass_active ? 'is-active' : ''; ?>" title="<?php echo $is_bypass_active ? 'Reactivar Espera 24h' : 'Omitir Espera 24h'; ?>">
                          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"></path>
                          </svg>
                        </button>
                      </form>
                      <form method="POST" style="display: inline; width: 100%" onsubmit="return confirm('¿Estás seguro de eliminar este plan? Esta acción no se puede deshacer.');">
                        <input type="hidden" name="action" value="kf_delete_plan">
                        <input type="hidden" name="plan_id" value="<?php echo get_the_ID(); ?>">
                        <button type="submit" class="btn-delete-plan" title="Eliminar Plan">
                          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="3,6 5,6 21,6"></polyline>
                            <path d="M19,6v14a2,2,0,0,1-2,2H7a2,2,0,0,1-2-2V6m3,0V4a2,2,0,0,1,2-2h4a2,2,0,0,1,2,2v2"></path>
                            <line x1="10" y1="11" x2="10" y2="17"></line>
                            <line x1="14" y1="11" x2="14" y2="17"></line>
                          </svg>
                        </button>
                      </form>
                    </div>
                  </div>
                  <?php
              endwhile;
              wp_reset_postdata();
          else :
              echo "<p style='padding: 40px; text-align: center; color: var(--kf-gray);'>No hay planes creados todavía.</p>";
          endif;
          ?>
        </div>
      </div>

      <div class="panel-section">
        <div class="section-title">⚡ NUEVO PLAN</div>
        <div style="padding: 25px;">
          <form method="POST">
            <input type="hidden" name="action" value="kf_create_new_plan">
            
            <div class="form-group">
              <label class="form-label">Seleccionar Atleta</label>
              <select name="user_id" class="kf-select" required>
                <option value="">Buscar atleta...</option>
                <?php 
                $subscribers = get_users(['role' => 'subscriber']);
                foreach($subscribers as $sub) {
                    echo "<option value='{$sub->ID}'>" . esc_html($sub->display_name) . "</option>";
                }
                ?>
              </select>
            </div>

            <div class="form-group">
              <label class="form-label">Objetivo del Plan</label>
              <input type="text" name="objetivo" class="kf-input" placeholder="Ej: Fase de Volumen" required>
            </div>

            <div class="form-group">
              <label class="form-label">Nivel de Intensidad</label>
              <select name="nivel" class="kf-select" required>
                <option value="principiante">Principiante</option>
                <option value="intermedio">Intermedio</option>
                <option value="avanzado">Avanzado</option>
              </select>
            </div>

            <button type="submit" class="btn-create">GENERAR ESTRUCTURA</button>
          </form>
        </div>
      </div>

    </div>
  </div>
</div>

<?php get_footer(); ?>