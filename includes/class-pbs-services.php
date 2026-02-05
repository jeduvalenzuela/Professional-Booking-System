<?php
/**
 * Gestión de servicios
 *
 * @package Professional_Booking_System
 */

if (!defined('ABSPATH')) {
    exit;
}

class PBS_Services {

    /**
     * Tabla de servicios
     */
    public static function get_table_name(): string {
        global $wpdb;
        return $wpdb->prefix . 'pbs_services';
    }

    /**
     * Crear servicio
     */
    public static function create( array $data ): int|WP_Error {
        global $wpdb;

        $defaults = array(
            'name'            => '',
            'description'     => '',
            'duration'        => 60,
            'price'           => 0.00,
            'enable_videocall'=> 0,
            'category'        => null,
            'status'          => 'active',
        );

        $data = wp_parse_args($data, $defaults);

        $result = $wpdb->insert(
            self::get_table_name(),
            array(
                'name'             => sanitize_text_field($data['name']),
                'description'      => wp_kses_post($data['description']),
                'duration'         => (int) $data['duration'],
                'price'            => floatval($data['price']),
                'enable_videocall' => !empty($data['enable_videocall']) ? 1 : 0,
                'category'         => $data['category'] ? sanitize_text_field($data['category']) : null,
                'status'           => sanitize_text_field($data['status']),
            ),
            array('%s', '%s', '%d', '%f', '%d', '%s', '%s')
        );

        if ($result === false) {
            return new WP_Error('pbs_service_create_failed', __('No se pudo crear el servicio.', 'professional-booking-system'));
        }

        return $wpdb->insert_id;
    }

    /**
     * Actualizar servicio
     */
    public static function update( int $id, array $data ): bool|WP_Error {
        global $wpdb;

        $id = (int) $id;
        if ($id <= 0) {
            return new WP_Error('pbs_invalid_service_id', __('ID de servicio inválido.', 'professional-booking-system'));
        }

        $fields = array();
        $formats = array();

        if (isset($data['name'])) {
            $fields['name'] = sanitize_text_field($data['name']);
            $formats[] = '%s';
        }
        if (isset($data['description'])) {
            $fields['description'] = wp_kses_post($data['description']);
            $formats[] = '%s';
        }
        if (isset($data['duration'])) {
            $fields['duration'] = (int) $data['duration'];
            $formats[] = '%d';
        }
        if (isset($data['price'])) {
            $fields['price'] = floatval($data['price']);
            $formats[] = '%f';
        }
        if (isset($data['enable_videocall'])) {
            $fields['enable_videocall'] = !empty($data['enable_videocall']) ? 1 : 0;
            $formats[] = '%d';
        }
        if (isset($data['category'])) {
            $fields['category'] = $data['category'] ? sanitize_text_field($data['category']) : null;
            $formats[] = '%s';
        }
        if (isset($data['status'])) {
            $fields['status'] = sanitize_text_field($data['status']);
            $formats[] = '%s';
        }

        if (empty($fields)) {
            return false;
        }

        $result = $wpdb->update(
            self::get_table_name(),
            $fields,
            array('id' => $id),
            $formats,
            array('%d')
        );

        if ($result === false) {
            return new WP_Error('pbs_service_update_failed', __('No se pudo actualizar el servicio.', 'professional-booking-system'));
        }

        return true;
    }

    /**
     * Eliminar (borrado lógico) servicio
     */
    public static function delete( int $id ): bool|WP_Error {
        return self::update($id, array('status' => 'inactive'));
    }

    /**
     * Obtener un servicio
     */
    public static function get( int $id ): ?array {
        global $wpdb;

        $id = (int) $id;
        if ($id <= 0) {
            return null;
        }

        $sql = $wpdb->prepare(
            "SELECT * FROM " . self::get_table_name() . " WHERE id = %d",
            $id
        );

        return $wpdb->get_row($sql, ARRAY_A);
    }

    /**
     * Obtener un servicio (alias para compatibilidad)
     */
    public static function get_service( int $id ): ?array {
        return self::get($id);
    }

    /**
     * Listar servicios
     */
    public static function get_all( array $args = array() ): array {
        global $wpdb;

        $defaults = array(
            'status'  => 'active', // 'all' para todos
            'orderby' => 'name',
            'order'   => 'ASC',
            'limit'   => 0,
            'offset'  => 0,
        );

        $args = wp_parse_args($args, $defaults);

        $where = 'WHERE 1=1';

        if ($args['status'] !== 'all') {
            $where .= $wpdb->prepare(' AND status = %s', $args['status']);
        }

        $orderby_whitelist = array('name', 'price', 'duration', 'created_at');
        $orderby = in_array($args['orderby'], $orderby_whitelist, true) ? $args['orderby'] : 'name';

        $order = strtoupper($args['order']) === 'DESC' ? 'DESC' : 'ASC';

        $limit_sql = '';
        if ($args['limit'] > 0) {
            $limit_sql = $wpdb->prepare(' LIMIT %d OFFSET %d', $args['limit'], $args['offset']);
        }

        $sql = "
            SELECT *
            FROM " . self::get_table_name() . "
            $where
            ORDER BY $orderby $order
            $limit_sql
        ";

        return $wpdb->get_results($sql, ARRAY_A) ?? array();
    }

    /**
     * Verificar si un servicio está activo
     */
    public static function is_active( int $id ): bool {
        $service = self::get($id);
        return $service && isset( $service['status'] ) && $service['status'] === 'active';
    }
}