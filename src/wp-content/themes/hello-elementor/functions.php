<?php
/**
 * Theme functions and definitions
 *
 * @package HelloElementor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Incluir el handler de sincronización ACF
require_once __DIR__ . '/acf-sync-handler.php';

define( 'HELLO_ELEMENTOR_VERSION', '3.4.5' );
define( 'EHP_THEME_SLUG', 'hello-elementor' );

define( 'HELLO_THEME_PATH', get_template_directory() );
define( 'HELLO_THEME_URL', get_template_directory_uri() );
define( 'HELLO_THEME_ASSETS_PATH', HELLO_THEME_PATH . '/assets/' );
define( 'HELLO_THEME_ASSETS_URL', HELLO_THEME_URL . '/assets/' );
define( 'HELLO_THEME_SCRIPTS_PATH', HELLO_THEME_ASSETS_PATH . 'js/' );
define( 'HELLO_THEME_SCRIPTS_URL', HELLO_THEME_ASSETS_URL . 'js/' );
define( 'HELLO_THEME_STYLE_PATH', HELLO_THEME_ASSETS_PATH . 'css/' );
define( 'HELLO_THEME_STYLE_URL', HELLO_THEME_ASSETS_URL . 'css/' );
define( 'HELLO_THEME_IMAGES_PATH', HELLO_THEME_ASSETS_PATH . 'images/' );
define( 'HELLO_THEME_IMAGES_URL', HELLO_THEME_ASSETS_URL . 'images/' );

if ( ! isset( $content_width ) ) {
	$content_width = 800; // Pixels.
}

if ( ! function_exists( 'hello_elementor_setup' ) ) {
	/**
	 * Set up theme support.
	 *
	 * @return void
	 */
	function hello_elementor_setup() {
		if ( is_admin() ) {
			hello_maybe_update_theme_version_in_db();
		}

		if ( apply_filters( 'hello_elementor_register_menus', true ) ) {
			register_nav_menus( [ 'menu-1' => esc_html__( 'Header', 'hello-elementor' ) ] );
			register_nav_menus( [ 'menu-2' => esc_html__( 'Footer', 'hello-elementor' ) ] );
		}

		if ( apply_filters( 'hello_elementor_post_type_support', true ) ) {
			add_post_type_support( 'page', 'excerpt' );
		}

		if ( apply_filters( 'hello_elementor_add_theme_support', true ) ) {
			add_theme_support( 'post-thumbnails' );
			add_theme_support( 'automatic-feed-links' );
			add_theme_support( 'title-tag' );
			add_theme_support(
				'html5',
				[
					'search-form',
					'comment-form',
					'comment-list',
					'gallery',
					'caption',
					'script',
					'style',
					'navigation-widgets',
				]
			);
			add_theme_support(
				'custom-logo',
				[
					'height'      => 100,
					'width'       => 350,
					'flex-height' => true,
					'flex-width'  => true,
				]
			);
			add_theme_support( 'align-wide' );
			add_theme_support( 'responsive-embeds' );

			/*
			 * Editor Styles
			 */
			add_theme_support( 'editor-styles' );
			add_editor_style( 'assets/css/editor-styles.css' );

			/*
			 * WooCommerce.
			 */
			if ( apply_filters( 'hello_elementor_add_woocommerce_support', true ) ) {
				// WooCommerce in general.
				add_theme_support( 'woocommerce' );
				// Enabling WooCommerce product gallery features (are off by default since WC 3.0.0).
				// zoom.
				add_theme_support( 'wc-product-gallery-zoom' );
				// lightbox.
				add_theme_support( 'wc-product-gallery-lightbox' );
				// swipe.
				add_theme_support( 'wc-product-gallery-slider' );
			}
		}
	}
}
add_action( 'after_setup_theme', 'hello_elementor_setup' );

function hello_maybe_update_theme_version_in_db() {
	$theme_version_option_name = 'hello_theme_version';
	// The theme version saved in the database.
	$hello_theme_db_version = get_option( $theme_version_option_name );

	// If the 'hello_theme_version' option does not exist in the DB, or the version needs to be updated, do the update.
	if ( ! $hello_theme_db_version || version_compare( $hello_theme_db_version, HELLO_ELEMENTOR_VERSION, '<' ) ) {
		update_option( $theme_version_option_name, HELLO_ELEMENTOR_VERSION );
	}
}

if ( ! function_exists( 'hello_elementor_display_header_footer' ) ) {
	/**
	 * Check whether to display header footer.
	 *
	 * @return bool
	 */
	function hello_elementor_display_header_footer() {
		$hello_elementor_header_footer = true;

		return apply_filters( 'hello_elementor_header_footer', $hello_elementor_header_footer );
	}
}

if ( ! function_exists( 'hello_elementor_scripts_styles' ) ) {
	/**
	 * Theme Scripts & Styles.
	 *
	 * @return void
	 */
	function hello_elementor_scripts_styles() {
		if ( apply_filters( 'hello_elementor_enqueue_style', true ) ) {
			wp_enqueue_style(
				'hello-elementor',
				HELLO_THEME_STYLE_URL . 'reset.css',
				[],
				HELLO_ELEMENTOR_VERSION
			);
		}

		if ( apply_filters( 'hello_elementor_enqueue_theme_style', true ) ) {
			wp_enqueue_style(
				'hello-elementor-theme-style',
				HELLO_THEME_STYLE_URL . 'theme.css',
				[],
				HELLO_ELEMENTOR_VERSION
			);
		}

		if ( hello_elementor_display_header_footer() ) {
			wp_enqueue_style(
				'hello-elementor-header-footer',
				HELLO_THEME_STYLE_URL . 'header-footer.css',
				[],
				HELLO_ELEMENTOR_VERSION
			);
		}
	}
}
add_action( 'wp_enqueue_scripts', 'hello_elementor_scripts_styles' );

if ( ! function_exists( 'hello_elementor_register_elementor_locations' ) ) {
	/**
	 * Register Elementor Locations.
	 *
	 * @param ElementorPro\Modules\ThemeBuilder\Classes\Locations_Manager $elementor_theme_manager theme manager.
	 *
	 * @return void
	 */
	function hello_elementor_register_elementor_locations( $elementor_theme_manager ) {
		if ( apply_filters( 'hello_elementor_register_elementor_locations', true ) ) {
			$elementor_theme_manager->register_all_core_location();
		}
	}
}
add_action( 'elementor/theme/register_locations', 'hello_elementor_register_elementor_locations' );

if ( ! function_exists( 'hello_elementor_content_width' ) ) {
	/**
	 * Set default content width.
	 *
	 * @return void
	 */
	function hello_elementor_content_width() {
		$GLOBALS['content_width'] = apply_filters( 'hello_elementor_content_width', 800 );
	}
}
add_action( 'after_setup_theme', 'hello_elementor_content_width', 0 );

if ( ! function_exists( 'hello_elementor_add_description_meta_tag' ) ) {
	/**
	 * Add description meta tag with excerpt text.
	 *
	 * @return void
	 */
	function hello_elementor_add_description_meta_tag() {
		if ( ! apply_filters( 'hello_elementor_description_meta_tag', true ) ) {
			return;
		}

		if ( ! is_singular() ) {
			return;
		}

		$post = get_queried_object();
		if ( empty( $post->post_excerpt ) ) {
			return;
		}

		echo '<meta name="description" content="' . esc_attr( wp_strip_all_tags( $post->post_excerpt ) ) . '">' . "\n";
	}
}
add_action( 'wp_head', 'hello_elementor_add_description_meta_tag' );

// Settings page
require get_template_directory() . '/includes/settings-functions.php';

// Header & footer styling option, inside Elementor
require get_template_directory() . '/includes/elementor-functions.php';

if ( ! function_exists( 'hello_elementor_customizer' ) ) {
	// Customizer controls
	function hello_elementor_customizer() {
		if ( ! is_customize_preview() ) {
			return;
		}

		if ( ! hello_elementor_display_header_footer() ) {
			return;
		}

		require get_template_directory() . '/includes/customizer-functions.php';
	}
}
add_action( 'init', 'hello_elementor_customizer' );

if ( ! function_exists( 'hello_elementor_check_hide_title' ) ) {
	/**
	 * Check whether to display the page title.
	 *
	 * @param bool $val default value.
	 *
	 * @return bool
	 */
	function hello_elementor_check_hide_title( $val ) {
		if ( defined( 'ELEMENTOR_VERSION' ) ) {
			$current_doc = Elementor\Plugin::instance()->documents->get( get_the_ID() );
			if ( $current_doc && 'yes' === $current_doc->get_settings( 'hide_title' ) ) {
				$val = false;
			}
		}
		return $val;
	}
}
add_filter( 'hello_elementor_page_title', 'hello_elementor_check_hide_title' );

/**
 * BC:
 * In v2.7.0 the theme removed the `hello_elementor_body_open()` from `header.php` replacing it with `wp_body_open()`.
 * The following code prevents fatal errors in child themes that still use this function.
 */
if ( ! function_exists( 'hello_elementor_body_open' ) ) {
	function hello_elementor_body_open() {
		wp_body_open();
	}
}

require HELLO_THEME_PATH . '/theme.php';

HelloTheme\Theme::instance();

/**
 * Bloquear el acceso al wp-admin para suscriptores
 */
function krysion_block_admin_access() {
    if ( is_admin() && ! current_user_can( 'administrator' ) && ! ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
        wp_safe_redirect( '/encuesta/area_privada.php' );
        exit;
    }
}
add_action( 'admin_init', 'krysion_block_admin_access' );

/**
 * Ocultar la barra de herramientas superior para suscriptores
 */
if ( ! current_user_can( 'administrator' ) ) {
    add_filter( 'show_admin_bar', '__return_false' );
}

/**
 * Proteger páginas específicas de Krysion Fit para usuarios no logueados
 */
add_action( 'template_redirect', 'krysion_protect_specific_pages' );

function krysion_protect_specific_pages() {
    // Si el usuario NO está logueado
    if ( ! is_user_logged_in() ) {
        if ( is_page('planes-de-entrenamiento-y-nutricion') ) {
            wp_safe_redirect( site_url( '/encuesta/login.php' ) ); 
            exit;
        }else if (is_page('wp-admin')){
            wp_safe_redirect( site_url( '/encuesta/login.php' ) );
            exit;
        }
    } 
    // Si ESTÁ logueado pero NO es administrador
    else if ( ! current_user_can('administrator') ) {
         if ( is_page('planes-de-entrenamiento-y-nutricion') ) {
            wp_safe_redirect( home_url( '/encuesta/login.php' ) ); // Lo mandamos a su área
            exit;
        }
    }
}

/**
 * REDIRECCIÓN AL CERRAR SESIÓN: Sistema personalizado
 */
// 1. Crear una URL de logout personalizada
add_filter('logout_url', 'krysion_custom_logout_url', 999, 2);

function krysion_custom_logout_url($logout_url, $redirect) {
    // Siempre usar nuestra URL personalizada para todos los enlaces de logout
    return home_url('/encuesta/logout.php');
}

// 2. Procesar el logout personalizado
add_action('init', 'krysion_handle_custom_logout');

function krysion_handle_custom_logout() {
    if (isset($_GET['krysion_logout']) && $_GET['krysion_logout'] == '1') {
        wp_logout();
        wp_safe_redirect(home_url('/encuesta/login.php'));
        exit();
    }
}

// 3. Resaldo por si acaso
add_action('wp_logout', 'krysion_logout_redirect_action');

function krysion_logout_redirect_action() {
    wp_safe_redirect(home_url('/encuesta/login.php'));
    exit();
}

/**
 * REDIRECCIÓN FINAL: Solución para Grupos de Usuarios ACF en Krysion Fit
 */
add_filter( 'login_redirect', 'krysion_perfect_redirection', 999, 3 );

function krysion_perfect_redirection( $redirect_to, $request, $user ) {
    // Si hay error de login, no hacer nada
    if ( is_wp_error( $user ) || ! isset( $user->roles ) ) {
        return $redirect_to;
    }

    // 1. ADMINISTRADOR: Al panel de creación de planes
    if ( in_array( 'administrator', $user->roles ) ) {
        return home_url( '/crear-plan/' );
    }

    // 2. SUSCRIPTOR: Buscar su plan personalizado
    if ( in_array( 'subscriber', $user->roles ) ) {
        
        $user_id = $user->ID;

        $args = array(
            'post_type'      => 'mi-plan',
            'posts_per_page' => 1,
            'meta_query'     => array(
                array(
                    'key'     => 'cliente', // Nombre del campo en ACF
                    // El formato "Grupo de Usuarios" guarda los datos serializados. 
                    // Buscamos el ID del usuario como un valor exacto dentro de esa cadena.
                    'value'   => '"' . $user_id . '"', 
                    'compare' => 'LIKE'
                )
            )
        );

        $query = new WP_Query( $args );

        if ( $query->have_posts() ) {
            $query->the_post();
            $url_plan = get_permalink();
            wp_reset_postdata();
            return $url_plan; // Enviarlo a su rutina naranja/negro
        } else {
            // Si no tiene plan, enviarlo a la página de cortesía
            return home_url( '/encuesta/login.php' ); 
        }
    }

    return $redirect_to;
}
// 1. EL RECEPTOR DE LA ORDEN (AJAX)
add_action('wp_ajax_ejecutar_ia_krysion', 'krysion_handle_ia_ajax');

function krysion_handle_ia_ajax() {
    // Verificar que venga el ID del post
    if ( !isset($_POST['post_id']) ) {
        wp_send_json_error('No se recibió el ID del plan');
    }

    $post_id = intval($_POST['post_id']);
    
    // Obtener los datos del cliente que ya tienes en ACF
    // Asegúrate de que estos nombres ('objetivo', 'nivel') coincidan con tus slugs de ACF
    $objetivo = get_field('objetivo', $post_id); 
    $nivel = get_field('nivel', $post_id); 

    // Llamamos a la función que conecta con OpenAI
    $rutina = generar_rutina_ia_krysion([
        'objetivo' => $objetivo,
        'nivel'    => $nivel
    ]);

    // Dentro de krysion_handle_ia_ajax, reemplaza el bloque de mapeo por este:
    if ( $rutina && is_array($rutina) ) {
        $dias = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo'];
        
        foreach ($dias as $dia) {
            $valor = $rutina[$dia];
    
            // Si por algún motivo la IA devuelve un array en lugar de string, lo convertimos
            if ( is_array($valor) ) {
                $valor = '<ul><li>' . implode('</li><li>', $valor) . '</li></ul>';
            }
    
            // update_field guardará el HTML en el editor visual (WYSIWYG)
            update_field('rutina_' . $dia, $valor, $post_id);
        }
        
        wp_send_json_success('Rutina generada en lista');
    }
}

// 2. LA FUNCIÓN QUE HABLA CON OPENAI (CON GPT-4o mini)
function generar_rutina_ia_krysion($datos) {
    $api_key = getenv('OPENAI_API_KEY'); // Lee la clave desde variable de entorno

    $prompt = "Genera una rutina de gimnasio profesional. 
           IMPORTANTE: Para cada día, el contenido DEBE ser una lista desordenada HTML (usando etiquetas <ul> y <li>).
           Objetivo: " . $datos['objetivo'] . ".
           Responde estrictamente en formato JSON con las llaves: lunes, martes, miercoles, jueves, viernes, sabado, domingo.";

    $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
        'headers' => [
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type'  => 'application/json',
        ],
        'body' => json_encode([
            'model' => 'gpt-4o-mini',
            'messages' => [
                ['role' => 'system', 'content' => 'Eres un entrenador experto de Krysion Fit.'],
                ['role' => 'user', 'content' => $prompt]
            ],
            'response_format' => ['type' => 'json_object'],
            'temperature' => 0.7
        ]),
        'timeout' => 40
    ]);

    if ( is_wp_error($response) ) return false;

    $body = json_decode(wp_remote_retrieve_body($response), true);
    $content = $body['choices'][0]['message']['content'];

    return json_decode($content, true);
}
// Cambiamos la ubicación para que funcione en el editor moderno (Gutenberg)
add_action('add_meta_boxes', 'krysion_registrar_metabox_ia');

function krysion_registrar_metabox_ia() {
    add_meta_box(
        'krysion_ia_box',          // ID único
        'Asistente de IA Krysion', // Título que verá Freddy
        'krysion_ia_box_html',     // Función que dibuja el contenido
        'mi-plan',                 // Tu Custom Post Type
        'side',                    // Ubicación: Barra lateral derecha
        'high'                     // Prioridad alta (arriba)
    );
}

function krysion_ia_box_html($post) {
    echo '<div style="padding:10px; text-align:center;">
            <p style="font-size:12px; margin-bottom:10px;">Genera la rutina automáticamente basada en los objetivos del cliente.</p>
            <button type="button" id="generar-con-ia" class="button button-primary" style="background:#ff6600; border-color:#ff6600; width:100%; height:40px; font-weight:bold;">
                ✨ Generar con GPT-4o mini
            </button>
            <p id="ia-status" style="margin-top:10px; font-weight:bold; color:#ff6600;"></p>
          </div>';
}


/**
 * INYECTAR EL SCRIPT DE AJAX EN EL ADMIN
 */
add_action('admin_footer', 'krysion_ia_script_js');

function krysion_ia_script_js() {
    // Solo cargar este script si estamos editando el post type 'mi-plan'
    $screen = get_current_screen();
    if ( ! $screen || $screen->post_type !== 'mi-plan' ) return;
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        $('#generar-con-ia').on('click', function() {
            const postId = $('#post_ID').val();
            const status = $('#ia-status');

            status.text('⏳ Conectando con Krysion AI...');
            $(this).prop('disabled', true);

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'ejecutar_ia_krysion', // Este nombre debe coincidir con el del backend
                    post_id: postId
                },
                success: function(response) {
                    if(response.success) {
                        status.text('✅ ¡Plan generado! Recargando...');
                        setTimeout(function(){
                            location.reload(); 
                        }, 1000);
                    } else {
                        status.text('❌ Error: ' + response.data);
                        $('#generar-con-ia').prop('disabled', false);
                    }
                },
                error: function() {
                    status.text('❌ Error de conexión al servidor.');
                    $('#generar-con-ia').prop('disabled', false);
                }
            });
        });
    });
    </script>
    <?php
}

// 1. Registrar la acción para usuarios logueados (Seguridad)
add_action('wp_ajax_save_training_log', 'krysion_save_training_log');

function krysion_save_training_log() {
    global $wpdb;

    // Verificar que el usuario tenga sesión activa (Límite de acceso)
    if (!is_user_logged_in()) {
        wp_send_json_error('No autorizado');
    }

    $table_name = 'wp_krysion_training_logs'; 

    // Sanitización de datos (Limpieza de inputs)
    $data = [
        'user_id'       => get_current_user_id(),
        'exercise_name' => sanitize_text_field($_POST['exercise']),
        'weight'        => floatval($_POST['weight']),
        'reps'          => intval($_POST['reps']),
        'rpe'           => intval($_POST['rpe']),
        'notes'         => sanitize_textarea_field($_POST['notes']),
    ];

    // Insertar en la base de datos
    $inserted = $wpdb->insert($table_name, $data);

    if ($inserted) {
        wp_send_json_success('¡Progreso guardado, máquina!');
    } else {
        wp_send_json_error('Error al guardar en la DB');
    }
}
/**
 * LÓGICA DE SEGUIMIENTO - KRYSIONFIT
 */

// Guardar Log de Entrenamiento
add_action('wp_ajax_kf_save_workout_log', 'kf_handle_workout_log');
function kf_handle_workout_log() {
    $payload = json_decode(stripslashes($_POST['payload']), true);
    $post_id = url_to_postid(wp_get_referer()); // Obtenemos el ID del plan desde donde viene
    $week = sanitize_text_field($_POST['week']);
    
    // Obtenemos el día: si viene por POST (manual) o calculamos el actual (automático)
    if ( !empty($_POST['day']) ) {
        $slug_dia = sanitize_text_field($_POST['day']);
    } else {
        $dia_actual = strtolower(date_i18n('l')); 
        $dias_map = ['monday'=>'lunes', 'tuesday'=>'martes', 'wednesday'=>'miercoles', 'thursday'=>'jueves', 'friday'=>'viernes', 'saturday'=>'sabado', 'sunday'=>'domingo'];
        $slug_dia = isset($dias_map[$dia_actual]) ? $dias_map[$dia_actual] : 'lunes';
    }

    if ($payload && $post_id) {
        // Guardamos los datos técnicos en un meta único por día/semana
        update_post_meta($post_id, "log_entrenamiento_{$week}_{$slug_dia}", $payload);
        // Marcamos como completado para el chulito verde
        update_post_meta($post_id, "completado_{$week}_{$slug_dia}", true);
        
        wp_send_json_success(['message' => 'Entrenamiento guardado']);
    }
    wp_send_json_error(['message' => 'Error al guardar']);
}

// Guardar Log de Nutrición
add_action('wp_ajax_kf_save_food_log', 'kf_handle_food_log');
function kf_handle_food_log() {
    $payload = json_decode(stripslashes($_POST['payload']), true);
    $post_id = url_to_postid(wp_get_referer());
    $week = sanitize_text_field($_POST['week']);
    
    // Obtenemos el día (igual que en entrenamiento)
    if ( !empty($_POST['day']) ) {
        $slug_dia = sanitize_text_field($_POST['day']);
    } else {
        $dia_actual = strtolower(date_i18n('l')); 
        $dias_map = ['monday'=>'lunes', 'tuesday'=>'martes', 'wednesday'=>'miercoles', 'thursday'=>'jueves', 'friday'=>'viernes', 'saturday'=>'sabado', 'sunday'=>'domingo'];
        $slug_dia = isset($dias_map[$dia_actual]) ? $dias_map[$dia_actual] : 'lunes';
    }

    if ($payload && $post_id) {
        // Guardamos por día para que el seguimiento sea diario como se pidió
        update_post_meta($post_id, "log_nutricion_{$week}_{$slug_dia}", $payload);
        // Marcamos como completado
        update_post_meta($post_id, "completado_nut_{$week}_{$slug_dia}", true);
        
        wp_send_json_success(['message' => 'Dieta guardada']);
    }
    wp_send_json_error();
}

// Sistema de Chat (Comentarios de WP)
add_action('wp_ajax_kf_send_chat_msg', 'kf_handle_chat_msg');
function kf_handle_chat_msg() {
    $msg = sanitize_textarea_field($_POST['msg']);
    $post_id = intval($_POST['post_id']);
    $user_id = get_current_user_id();

    if ($msg && $post_id) {
        $comment_id = wp_insert_comment([
            'comment_post_ID'      => $post_id,
            'comment_content'      => $msg,
            'user_id'              => $user_id,
            'comment_author'       => wp_get_current_user()->display_name,
            'comment_approved'     => 1,
            'comment_type'         => 'comment'
        ]);
        wp_send_json_success();
    }
    wp_send_json_error();
}

// Obtener datos de encuesta (goal y activity_level) por correo
add_action('wp_ajax_kf_get_user_survey_data', 'kf_get_user_survey_data');

function kf_get_user_survey_data() {
    global $wpdb;

    if ( ! is_user_logged_in() || ! current_user_can('administrator') ) {
        wp_send_json_error(['error' => 'No autorizado']);
    }

    if ( empty($_GET['email']) ) {
        wp_send_json_error(['error' => 'Email parameter missing']);
    }

    $email = sanitize_email( wp_unslash($_GET['email']) );
    if ( empty($email) ) {
        wp_send_json_error(['error' => 'Invalid email']);
    }

    $table = 'planes_personalizados';

    $row = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT goal, activity_level FROM {$table} WHERE email = %s ORDER BY created_at DESC LIMIT 1",
            $email
        ),
        ARRAY_A
    );

    if ( empty($row) ) {
        wp_send_json_error(['error' => 'User not found']);
    }

    wp_send_json_success([
        'goal' => $row['goal'] ?? null,
        'activity_level' => $row['activity_level'] ?? null,
    ]);
}