<?php
/**
 * Gestión de horarios y excepciones
 *
 * @package Professional_Booking_System
 */

if (!defined('ABSPATH')) {
    exit;
}

class PBS_Schedules {

    public static function get_table_schedules() {
        global $wpdb;
        return $wpdb->prefix . 'pbs_schedules';
    }

    public static function get_table_exceptions() {
        global $wpdb;
        return $wpdb->prefix . 'pbs_exceptions';
    }

    /**
     * Crear horario
     */
    public static function create_schedule($data) {
        global $wpdb;

        $defaults = array(
            'day_of_week' => 1,          // 1=Lunes
            'start_time'  => '09:00:00',
            'end_time'    => '17:00:00',
            'is_active'   => 1,
        );

        $data = wp_parse_args($data, $defaults);

        $result = $wpdb->insert(
            self::get_table_schedules(),
            array(
                'day_of_week' => (int) $data['day_of_week'],
                'start_time'  => sanitize_text_field($data['start_time']),
                'end_time'    => sanitize_text_field($data['end_time']),
                'is_active'   => !empty($data['is_active']) ? 1 : 0,
            ),
            array('%d', '%s', '%s', '%d')
        );

        if ($result === false) {
            return new WP_Error('pbs_schedule_create_failed', __('No se pudo crear el horario.', 'professional-booking-system'));
        }

        return $wpdb->insert_id;
    }

    /**
     * Actualizar horario
     */
    public static function update_schedule($id, $data) {
        global $wpdb;

        $id = (int) $id;
        if ($id <= 0) {
            return new WP_Error('pbs_invalid_schedule_id', __('ID de horario inválido.', 'professional-booking-system'));
        }

        $fields = array();
        $formats = array();

        if (isset($data['day_of_week'])) {
            $fields['day_of_week'] = (int) $data['day_of_week'];
            $formats[] = '%d';
        }
        if (isset($data['start_time'])) {
            $fields['start_time'] = sanitize_text_field($data['start_time']);
            $formats[] = '%s';
        }
        if (isset($data['end_time'])) {
            $fields['end_time'] = sanitize_text_field($data['end_time']);
            $formats[] = '%s';
        }
        if (isset($data['is_active'])) {
            $fields['is_active'] = !empty($data['is_active']) ? 1 : 0;
            $formats[] = '%d';
        }

        if (empty($fields)) {
            return false;
        }

        $result = $wpdb->update(
            self::get_table_schedules(),
            $fields,
            array('id' => $id),
            $formats,
            array('%d')
        );

        if ($result === false) {
            return new WP_Error('pbs_schedule_update_failed', __('No se pudo actualizar el horario.', 'professional-booking-system'));
        }

        return true;
    }

    /**
     * Eliminar horario
     */
    public static function delete_schedule($id) {
        global $wpdb;

        $id = (int) $id;
        if ($id <= 0) {
            return false;
        }

        return (bool) $wpdb->delete(
            self::get_table_schedules(),
            array('id' => $id),
            array('%d')
        );
    }

    /**
     * Obtener horarios por día de la semana
     */
    public static function get_schedules_by_day($day_of_week, $only_active = true) {
        global $wpdb;

        $day_of_week = (int) $day_of_week;

        $where = $wpdb->prepare('WHERE day_of_week = %d', $day_of_week);
        if ($only_active) {
            $where .= ' AND is_active = 1';
        }

        $sql = "
            SELECT *
            FROM " . self::get_table_schedules() . "
            $where
            ORDER BY start_time ASC
        ";

        return $wpdb->get_results($sql);
    }

    /**
     * Crear excepción (día bloqueado, vacaciones, etc.)
     */
    public static function create_exception($data) {
        global $wpdb;

        $defaults = array(
            'exception_date' => '',       // Y-m-d
            'start_time'     => null,     // opcional
            'end_time'       => null,     // opcional
            'type'           => 'blocked',
            'reason'         => '',
        );

        $data = wp_parse_args($data, $defaults);

        if (empty($data['exception_date'])) {
            return new WP_Error('pbs_exception_date_required', __('La fecha de la excepción es obligatoria.', 'professional-booking-system'));
        }

        $result = $wpdb->insert(
            self::get_table_exceptions(),
            array(
                'exception_date' => sanitize_text_field($data['exception_date']),
                'start_time'     => $data['start_time'] ? sanitize_text_field($data['start_time']) : null,
                'end_time'       => $data['end_time'] ? sanitize_text_field($data['end_time']) : null,
                'type'           => sanitize_text_field($data['type']),
                'reason'         => wp_kses_post($data['reason']),
            ),
            array('%s', '%s', '%s', '%s', '%s')
        );

        if ($result === false) {
            return new WP_Error('pbs_exception_create_failed', __('No se pudo crear la excepción.', 'professional-booking-system'));
        }

        return $wpdb->insert_id;
    }

    /**
     * Obtener excepciones por fecha
     */
    public static function get_exceptions_by_date($date) {
        global $wpdb;

        $sql = $wpdb->prepare(
            "SELECT * FROM " . self::get_table_exceptions() . " WHERE exception_date = %s",
            $date
        );

        return $wpdb->get_results($sql);
    }

    /**
     * Comprobar si un día completo está bloqueado
     */
    public static function is_day_blocked($date) {
        global $wpdb;

        $sql = $wpdb->prepare(
            "SELECT COUNT(*) FROM " . self::get_table_exceptions() . " WHERE exception_date = %s AND (start_time IS NULL AND end_time IS NULL)",
            $date
        );

        $count = $wpdb->get_var($sql);

        return $count > 0;
    }
}