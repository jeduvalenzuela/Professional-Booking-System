<?php
/**
 * Gestión de reservas
 *
 * @package Professional_Booking_System
 */

if (!defined('ABSPATH')) {
    exit;
}

class PBS_Bookings {

    public static function get_table_bookings() {
        global $wpdb;
        return $wpdb->prefix . 'pbs_bookings';
    }

    public static function get_table_locks() {
        global $wpdb;
        return $wpdb->prefix . 'pbs_booking_locks';
    }

    /**
     * Crear bloqueo temporal de horario (durante pago)
     */
    public static function create_lock($service_id, $date, $time, $duration_minutes = 5) {
        global $wpdb;

        $service_id = (int) $service_id;

        PBS_Database::clean_expired_locks();

        $expires_at = gmdate(
            'Y-m-d H:i:s',
            time() + ($duration_minutes * 60)
        );

        $session_id = self::get_session_id();

        $result = $wpdb->insert(
            self::get_table_locks(),
            array(
                'booking_date' => sanitize_text_field($date),
                'booking_time' => sanitize_text_field($time),
                'service_id'   => $service_id,
                'session_id'   => sanitize_text_field($session_id),
                'expires_at'   => $expires_at,
            ),
            array('%s', '%s', '%d', '%s', '%s')
        );

        if ($result === false) {
            return new WP_Error('pbs_lock_create_failed', __('No se pudo crear el bloqueo de horario.', 'professional-booking-system'));
        }

        return $wpdb->insert_id;
    }

    /**
     * Eliminar bloqueos de la sesión actual
     */
    public static function clear_locks_for_current_session() {
        global $wpdb;

        $session_id = self::get_session_id();

        return $wpdb->delete(
            self::get_table_locks(),
            array('session_id' => $session_id),
            array('%s')
        );
    }

    /**
     * Obtener un identificador de sesión (simple, basado en cookies)
     */
    protected static function get_session_id() {
        if (!session_id()) {
            // No forzamos session_start por compatibilidad, usamos cookie propia
            if (empty($_COOKIE['pbs_session'])) {
                $session = wp_generate_uuid4();
                setcookie('pbs_session', $session, time() + DAY_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true);
                $_COOKIE['pbs_session'] = $session;
            }
            return sanitize_text_field($_COOKIE['pbs_session']);
        }

        return session_id();
    }

    /**
     * Comprobar si un horario está bloqueado o reservado
     */
    public static function is_slot_taken($service_id, $date, $time) {
        global $wpdb;

        $service_id = (int) $service_id;

        PBS_Database::clean_expired_locks();

        // Comprobar reservas confirmadas o pendientes de pago
        $sql_booking = $wpdb->prepare(
            "SELECT COUNT(*)
             FROM " . self::get_table_bookings() . "
             WHERE service_id = %d
               AND booking_date = %s
               AND booking_time = %s
               AND status IN ('pending', 'confirmed')",
            $service_id,
            $date,
            $time
        );

        $booked_count = (int) $wpdb->get_var($sql_booking);

        if ($booked_count > 0) {
            return true;
        }

        // Comprobar bloqueos temporales
        $sql_lock = $wpdb->prepare(
            "SELECT COUNT(*)
             FROM " . self::get_table_locks() . "
             WHERE service_id = %d
               AND booking_date = %s
               AND booking_time = %s
               AND expires_at > %s",
            $service_id,
            $date,
            $time,
            current_time('mysql')
        );

        $lock_count = (int) $wpdb->get_var($sql_lock);

        return $lock_count > 0;
    }

    /**
     * Crear reserva (sin procesar todavía el pago)
     */
    public static function create_booking($data) {
        global $wpdb;

        $defaults = array(
            'service_id'      => 0,
            'customer_name'   => '',
            'customer_email'  => '',
            'customer_phone'  => '',
            'booking_date'    => '',
            'booking_time'    => '',
            'duration'        => 60,
            'status'          => 'pending',
            'payment_status'  => 'pending',
            'payment_amount'  => 0.00,
            'payment_id'      => null,
            'videocall_link'  => null,
            'google_event_id' => null,
            'customer_notes'  => '',
            'admin_notes'     => '',
        );

        $data = wp_parse_args($data, $defaults);

        // Validaciones básicas
        if (empty($data['service_id']) || empty($data['customer_name']) || empty($data['customer_email']) || empty($data['booking_date']) || empty($data['booking_time'])) {
            return new WP_Error('pbs_booking_required_fields', __('Faltan campos obligatorios para crear la reserva.', 'professional-booking-system'));
        }

        if (!is_email($data['customer_email'])) {
            return new WP_Error('pbs_invalid_email', __('El email del cliente no es válido.', 'professional-booking-system'));
        }

        // Comprobar que el servicio existe y está activo
        if (!PBS_Services::is_active($data['service_id'])) {
            return new WP_Error('pbs_invalid_service', __('El servicio seleccionado no está disponible.', 'professional-booking-system'));
        }

        // Comprobar que el día no está bloqueado
        if (PBS_Schedules::is_day_blocked($data['booking_date'])) {
            return new WP_Error('pbs_day_blocked', __('La fecha seleccionada no está disponible.', 'professional-booking-system'));
        }

        // Comprobar que el slot no está tomado
        if (self::is_slot_taken($data['service_id'], $data['booking_date'], $data['booking_time'])) {
            return new WP_Error('pbs_slot_taken', __('El horario seleccionado ya no está disponible.', 'professional-booking-system'));
        }

        // Generar token de cancelación
        $cancellation_token = wp_generate_password(32, false);

        $result = $wpdb->insert(
            self::get_table_bookings(),
            array(
                'service_id'       => (int) $data['service_id'],
                'customer_name'    => sanitize_text_field($data['customer_name']),
                'customer_email'   => sanitize_email($data['customer_email']),
                'customer_phone'   => sanitize_text_field($data['customer_phone']),
                'booking_date'     => sanitize_text_field($data['booking_date']),
                'booking_time'     => sanitize_text_field($data['booking_time']),
                'duration'         => (int) $data['duration'],
                'status'           => sanitize_text_field($data['status']),
                'payment_status'   => sanitize_text_field($data['payment_status']),
                'payment_amount'   => floatval($data['payment_amount']),
                'payment_id'       => $data['payment_id'] ? sanitize_text_field($data['payment_id']) : null,
                'videocall_link'   => $data['videocall_link'] ? esc_url_raw($data['videocall_link']) : null,
                'google_event_id'  => $data['google_event_id'] ? sanitize_text_field($data['google_event_id']) : null,
                'customer_notes'   => wp_kses_post($data['customer_notes']),
                'admin_notes'      => wp_kses_post($data['admin_notes']),
                'cancellation_token' => $cancellation_token,
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%f', '%s', '%s', '%s', '%s', '%s', '%s')
        );

        if ($result === false) {
            return new WP_Error('pbs_booking_create_failed', __('No se pudo crear la reserva.', 'professional-booking-system'));
        }

        $booking_id = $wpdb->insert_id;

        return array(
            'id'                 => $booking_id,
            'cancellation_token' => $cancellation_token,
        );
    }

    /**
     * Cambiar estado de la reserva
     */
    public static function update_status($booking_id, $status) {
        global $wpdb;

        $booking_id = (int) $booking_id;
        if ($booking_id <= 0) {
            return new WP_Error('pbs_invalid_booking_id', __('ID de reserva inválido.', 'professional-booking-system'));
        }

        $allowed_statuses = array('pending', 'confirmed', 'completed', 'cancelled');

        if (!in_array($status, $allowed_statuses, true)) {
            return new WP_Error('pbs_invalid_booking_status', __('Estado de reserva inválido.', 'professional-booking-system'));
        }

        $result = $wpdb->update(
            self::get_table_bookings(),
            array('status' => $status),
            array('id' => $booking_id),
            array('%s'),
            array('%d')
        );

        if ($result === false) {
            return new WP_Error('pbs_booking_status_update_failed', __('No se pudo actualizar el estado de la reserva.', 'professional-booking-system'));
        }

        return true;
    }

    public function update_booking_status( $booking_id, $status ) {
        global $wpdb;
        $table = $this->table_bookings;

        $updated = $wpdb->update(
            $table,
            array(
                'status'       => $status,
                'updated_at'   => current_time( 'mysql' ),
            ),
            array( 'id' => $booking_id ),
            array( '%s', '%s' ),
            array( '%d' )
        );

        if ( $updated === false ) {
            return false;
        }

        // Si la reserva se confirma, intentamos crear evento en Google Calendar
        if ( $status === 'confirmed' && PBS_Google_Calendar::get_instance()->is_enabled() ) {
            $booking = $this->get_booking( $booking_id );
            if ( $booking ) {
                $service = PBS_Services::get_instance()->get_service( $booking['service_id'] );
                if ( $service ) {

                    // 1) Google Calendar / Meet
                    $result = PBS_Google_Calendar::get_instance()->create_event_for_booking( $booking, $service );
                    if ( $result['success'] ) {
                        $update_data = array(
                            'google_event_id' => $result['event_id'],
                        );
                        if ( ! empty( $result['meet_link'] ) ) {
                            $update_data['video_link'] = $result['meet_link'];
                        }

                        $wpdb->update(
                            $table,
                            $update_data,
                            array( 'id' => $booking_id ),
                            array_fill( 0, count( $update_data ), '%s' ),
                            array( '%d' )
                        );

                    }

                    // 2) Emails de notificación
                        $notifications = PBS_Notifications::get_instance();
                        $notifications->send_client_confirmation( $booking, $service );
                        $notifications->send_admin_notification( $booking, $service );
                }
            }
        }

        return true;
    }

    /**
     * Actualizar estado de pago
     */
    public static function update_payment_status($booking_id, $payment_status, $payment_id = null) {
        global $wpdb;

        $allowed_statuses = array('pending', 'paid', 'refunded');

        if (!in_array($payment_status, $allowed_statuses, true)) {
            return new WP_Error('pbs_invalid_payment_status', __('Estado de pago inválido.', 'professional-booking-system'));
        }

        $fields = array(
            'payment_status' => $payment_status,
        );
        $formats = array('%s');

        if ($payment_id !== null) {
            $fields['payment_id'] = sanitize_text_field($payment_id);
            $formats[] = '%s';
        }

        $result = $wpdb->update(
            self::get_table_bookings(),
            $fields,
            array('id' => (int) $booking_id),
            $formats,
            array('%d')
        );

        if ($result === false) {
            return new WP_Error('pbs_payment_status_update_failed', __('No se pudo actualizar el estado de pago.', 'professional-booking-system'));
        }

        return true;
    }

    /**
     * Obtener reserva por ID
     */
    public static function get($booking_id) {
        global $wpdb;

        $booking_id = (int) $booking_id;
        if ($booking_id <= 0) {
            return null;
        }

        $sql = $wpdb->prepare(
            "SELECT * FROM " . self::get_table_bookings() . " WHERE id = %d",
            $booking_id
        );

        return $wpdb->get_row($sql);
    }

    /**
     * Obtener reserva por token de cancelación
     */
    public static function get_by_cancellation_token($token) {
        global $wpdb;

        $sql = $wpdb->prepare(
            "SELECT * FROM " . self::get_table_bookings() . " WHERE cancellation_token = %s",
            $token
        );

        return $wpdb->get_row($sql);
    }

    /**
     * Obtener reservas para el admin con filtros + paginación
     *
     * @return array [bookings, total]
     */
    public function get_bookings_admin_list( $args = array() ) {
        global $wpdb;
        $table_bookings = $this->table_bookings;
        $table_services = PBS_Services::get_instance()->table_services;

        $defaults = array(
            'status'         => '',
            'payment_status' => '',
            'service_id'     => 0,
            'search'         => '',
            'paged'          => 1,
            'per_page'       => 20,
        );
        $args = wp_parse_args( $args, $defaults );

        $where   = array( '1=1' );
        $params  = array();

        if ( ! empty( $args['status'] ) ) {
            $where[]  = 'b.status = %s';
            $params[] = $args['status'];
        }

        if ( ! empty( $args['payment_status'] ) ) {
            $where[]  = 'b.payment_status = %s';
            $params[] = $args['payment_status'];
        }

        if ( ! empty( $args['service_id'] ) ) {
            $where[]  = 'b.service_id = %d';
            $params[] = $args['service_id'];
        }

        if ( ! empty( $args['search'] ) ) {
            $like = '%' . $wpdb->esc_like( $args['search'] ) . '%';
            $where[] = '(b.name LIKE %s OR b.email LIKE %s OR b.id = %d)';
            $params[] = $like;
            $params[] = $like;
            $params[] = intval( $args['search'] );
        }

        $where_sql = implode( ' AND ', $where );

        $offset = ( $args['paged'] - 1 ) * $args['per_page'];

        // Total
        $sql_total = "SELECT COUNT(*) FROM {$table_bookings} b WHERE {$where_sql}";
        $total     = $wpdb->get_var( $wpdb->prepare( $sql_total, $params ) );

        // Listado
        $sql = "SELECT b.*, s.name AS service_name
                FROM {$table_bookings} b
                LEFT JOIN {$table_services} s ON b.service_id = s.id
                WHERE {$where_sql}
                ORDER BY b.date DESC, b.time DESC
                LIMIT %d OFFSET %d";

        $params_list   = array_merge( $params, array( $args['per_page'], $offset ) );
        $prepared_sql  = $wpdb->prepare( $sql, $params_list );
        $rows          = $wpdb->get_results( $prepared_sql, ARRAY_A );

        return array( $rows, intval( $total ) );
    }
}