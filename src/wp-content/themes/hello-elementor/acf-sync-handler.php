<?php
/**
 * AJAX Handler para guardar datos hacia ACF
 */

add_action('wp_ajax_kf_save_to_acf', 'kf_save_to_acf_handler');
add_action('wp_ajax_nopriv_kf_save_to_acf', 'kf_save_to_acf_handler');

function kf_save_to_acf_handler() {
    // Verificar nonce
    if (!wp_verify_nonce($_POST['nonce'], 'kf_save_acf')) {
        wp_die('Security check failed');
    }
    
    // Verificar si es coach
    if (!current_user_can('administrator')) {
        wp_send_json_error(['message' => 'No tienes permisos para realizar esta acción']);
    }
    
    $post_id = intval($_POST['post_id']);
    $dias_slugs = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo'];
    
    // Obtener los datos enviados desde la plantilla
    $plan_data = json_decode(stripslashes($_POST['plan_data']), true);
    
    // Log para depuración
    error_log('=== KF ACF SYNC DEBUG ===');
    error_log('Post ID: ' . $post_id);
    error_log('Plan Data Received: ' . print_r($plan_data, true));
    
    // Verificar si los campos ACF existen
    foreach ($dias_slugs as $slug) {
        $field_name = 'rutina_' . $slug;
        $field_exists = get_field_object($field_name, $post_id);
        error_log("Field $field_name exists: " . ($field_exists ? 'YES' : 'NO'));
        if ($field_exists) {
            error_log("Field $field_name type: " . $field_exists['type']);
            error_log("Field $field_name current value: " . get_field($field_name, $post_id));
        }
    }
    
    if (!$plan_data || !is_array($plan_data)) {
        error_log('ERROR: No se recibieron datos válidos');
        wp_send_json_error(['message' => 'No se recibieron datos válidos']);
    }
    
    $saved_weeks = [];
    
    // Guardar datos de entrenamiento y nutrición
    for ($w = 1; $w <= 6; $w++) {
        $semana_key = "semana_$w";
        $nutricion_key = "nutricion_$w";
        
        // Guardar entrenamiento
        if (isset($plan_data[$semana_key]) && is_array($plan_data[$semana_key])) {
            $data_ent = $plan_data[$semana_key];
            error_log("Processing week $w - Entrenamiento: " . print_r($data_ent, true));
            
            // Guardar cada día en su campo ACF individual
            foreach ($dias_slugs as $slug) {
                if (isset($data_ent[$slug])) {
                    $field_name = 'rutina_' . $slug;
                    $field_value = $data_ent[$slug];
                    error_log("Saving field $field_name with value: $field_value");
                    
                    $result = update_field($field_name, $field_value, $post_id);
                    error_log("Update field result for $field_name: " . ($result ? 'SUCCESS' : 'FAILED'));
                }
            }
            
            // También guardar en campo de semana
            update_field($semana_key, $data_ent, $post_id);
            $saved_weeks[] = $w;
        }
        
        // Guardar nutrición
        if (isset($plan_data[$nutricion_key]) && is_array($plan_data[$nutricion_key])) {
            $data_nut = $plan_data[$nutricion_key];
            
            // Guardar cada día en su campo ACF individual si existe
            foreach ($dias_slugs as $slug) {
                if (isset($data_nut[$slug])) {
                    update_field('nutricion_' . $slug, $data_nut[$slug], $post_id);
                }
            }
            
            // También guardar en campo de nutrición
            update_field($nutricion_key, $data_nut, $post_id);
        }
    }
    
    // Guardar consejos del coach
    if (isset($plan_data['consejos_coach'])) {
        update_field('consejos_coach', $plan_data['consejos_coach'], $post_id);
    }
    
    error_log('=== END KF ACF SYNC DEBUG ===');
    
    wp_send_json_success([
        'message' => 'Datos guardados correctamente en ACF',
        'saved_weeks' => $saved_weeks,
        'debug' => $plan_data // Para depuración
    ]);
}
