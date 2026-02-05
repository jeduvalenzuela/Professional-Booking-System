<?php
/**
 * Seguridad mejorada - Rate Limiting, CSRF, Auditoría
 *
 * @package Professional_Booking_System
 */

if (!defined('ABSPATH')) {
    exit;
}

class PBS_Security {

    /**
     * Singleton instance
     * @var PBS_Security|null
     */
    private static $instance = null;

    /**
     * Get instance
     */
    public static function get_instance(): PBS_Security {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor privado
     */
    private function __construct() {
        add_action('admin_init', array($this, 'init_csrf'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_csrf'));
    }

    // ==================== CSRF TOKENS ====================

    /**
     * Inicializar CSRF token en admin
     */
    public function init_csrf(): void {
        if (!session_id()) {
            @session_start();
        }

        if (!isset($_SESSION['pbs_csrf_token'])) {
            $_SESSION['pbs_csrf_token'] = wp_generate_password(32, false);
        }
    }

    /**
     * Encolar CSRF token en frontend
     */
    public function enqueue_csrf(): void {
        wp_localize_script('pbs-frontend', 'pbsSecurity', array(
            'csrf_token' => $this->get_csrf_token(),
            'nonce' => wp_create_nonce('pbs_booking_nonce'),
        ));
    }

    /**
     * Obtener token CSRF
     */
    public function get_csrf_token(): string {
        if (!session_id()) {
            @session_start();
        }

        if (!isset($_SESSION['pbs_csrf_token'])) {
            $_SESSION['pbs_csrf_token'] = wp_generate_password(32, false);
        }

        return $_SESSION['pbs_csrf_token'];
    }

    /**
     * Verificar token CSRF
     */
    public function verify_csrf_token( string $token ): bool {
        if (!session_id()) {
            @session_start();
        }

        if (!isset($_SESSION['pbs_csrf_token'])) {
            return false;
        }

        return hash_equals($_SESSION['pbs_csrf_token'], $token);
    }

    // ==================== RATE LIMITING ====================

    /**
     * Obtener tabla de rate limiting
     */
    private function get_rate_limit_table(): string {
        global $wpdb;
        return $wpdb->prefix . 'pbs_rate_limits';
    }

    /**
     * Inicializar tabla de rate limiting
     */
    public function init_rate_limit_table(): void {
        global $wpdb;

        $table = $this->get_rate_limit_table();
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            ip_address VARCHAR(45) NOT NULL,
            endpoint VARCHAR(255) NOT NULL,
            attempts INT(11) DEFAULT 0,
            first_attempt DATETIME DEFAULT CURRENT_TIMESTAMP,
            last_attempt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY ip_endpoint (ip_address, endpoint),
            KEY last_attempt (last_attempt)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Obtener IP del cliente
     */
    private function get_client_ip(): string {
        if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            return sanitize_text_field($_SERVER['HTTP_CF_CONNECTING_IP']);
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = array_map('trim', explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']));
            return sanitize_text_field($ips[0]);
        } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            return sanitize_text_field($_SERVER['REMOTE_ADDR']);
        }
        return '0.0.0.0';
    }

    /**
     * Verificar y registrar intento (Rate Limiting)
     *
     * @param string $endpoint Identificador del endpoint
     * @param int $max_attempts Máximo número de intentos
     * @param int $window_seconds Ventana de tiempo en segundos
     */
    public function check_rate_limit( string $endpoint, int $max_attempts = 30, int $window_seconds = 60 ): bool {
        global $wpdb;

        $ip = $this->get_client_ip();
        $table = $this->get_rate_limit_table();
        $now = current_time('mysql');
        $window_start = gmdate('Y-m-d H:i:s', strtotime($now) - $window_seconds);

        // Obtener intentos recientes
        $attempt_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE ip_address = %s AND endpoint = %s AND last_attempt > %s",
            $ip,
            $endpoint,
            $window_start
        ));

        $attempt_count = (int) $attempt_count;

        if ($attempt_count >= $max_attempts) {
            return false; // Rate limit excedido
        }

        // Registrar intento
        $wpdb->query($wpdb->prepare(
            "INSERT INTO $table (ip_address, endpoint, attempts, first_attempt, last_attempt) 
             VALUES (%s, %s, 1, %s, %s)
             ON DUPLICATE KEY UPDATE 
             attempts = attempts + 1, 
             last_attempt = %s",
            $ip,
            $endpoint,
            $now,
            $now,
            $now
        ));

        return true; // Permitido
    }

    /**
     * Limpiar intentos expirados
     */
    public function cleanup_rate_limits(): void {
        global $wpdb;

        $table = $this->get_rate_limit_table();
        $expiry = gmdate('Y-m-d H:i:s', time() - (24 * 60 * 60)); // 24 horas

        $wpdb->query($wpdb->prepare(
            "DELETE FROM $table WHERE last_attempt < %s",
            $expiry
        ));
    }

    // ==================== AUDIT LOGGING ====================

    /**
     * Obtener tabla de auditoría
     */
    private function get_audit_table(): string {
        global $wpdb;
        return $wpdb->prefix . 'pbs_audit_logs';
    }

    /**
     * Inicializar tabla de auditoría
     */
    public function init_audit_table(): void {
        global $wpdb;

        $table = $this->get_audit_table();
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) UNSIGNED,
            action VARCHAR(255) NOT NULL,
            object_type VARCHAR(100),
            object_id INT(11),
            old_value LONGTEXT,
            new_value LONGTEXT,
            ip_address VARCHAR(45),
            user_agent TEXT,
            timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY action (action),
            KEY object_type (object_type),
            KEY timestamp (timestamp)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Registrar evento de auditoría
     */
    public function log_audit( string $action, string $object_type = null, int $object_id = null, mixed $old_value = null, mixed $new_value = null ): bool {
        global $wpdb;

        $table = $this->get_audit_table();
        $user_id = get_current_user_id();
        $ip = $this->get_client_ip();
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? substr(sanitize_text_field($_SERVER['HTTP_USER_AGENT']), 0, 255) : '';

        return (bool) $wpdb->insert(
            $table,
            array(
                'user_id' => $user_id ? $user_id : null,
                'action' => sanitize_text_field($action),
                'object_type' => $object_type ? sanitize_text_field($object_type) : null,
                'object_id' => $object_id,
                'old_value' => is_array($old_value) || is_object($old_value) ? wp_json_encode($old_value) : $old_value,
                'new_value' => is_array($new_value) || is_object($new_value) ? wp_json_encode($new_value) : $new_value,
                'ip_address' => $ip,
                'user_agent' => $user_agent,
            ),
            array('%d', '%s', '%s', '%d', '%s', '%s', '%s', '%s')
        );
    }

    /**
     * Obtener logs de auditoría
     */
    public function get_audit_logs( array $args = array() ): array {
        global $wpdb;

        $table = $this->get_audit_table();

        $defaults = array(
            'action' => null,
            'object_type' => null,
            'object_id' => null,
            'user_id' => null,
            'limit' => 100,
            'offset' => 0,
            'orderby' => 'timestamp',
            'order' => 'DESC',
        );

        $args = wp_parse_args($args, $defaults);
        $where = array('1=1');
        $params = array();

        if ($args['action']) {
            $where[] = 'action = %s';
            $params[] = $args['action'];
        }

        if ($args['object_type']) {
            $where[] = 'object_type = %s';
            $params[] = $args['object_type'];
        }

        if ($args['object_id']) {
            $where[] = 'object_id = %d';
            $params[] = $args['object_id'];
        }

        if ($args['user_id']) {
            $where[] = 'user_id = %d';
            $params[] = $args['user_id'];
        }

        $where_sql = implode(' AND ', $where);
        $order = strtoupper($args['order']) === 'DESC' ? 'DESC' : 'ASC';

        $sql = "SELECT * FROM $table WHERE $where_sql ORDER BY {$args['orderby']} $order LIMIT %d OFFSET %d";
        
        $params[] = $args['limit'];
        $params[] = $args['offset'];

        return $wpdb->get_results($wpdb->prepare($sql, $params), ARRAY_A) ?? array();
    }

    /**
     * Limpiar logs antiguos de auditoría (guardar 90 días)
     */
    public function cleanup_audit_logs(): void {
        global $wpdb;

        $table = $this->get_audit_table();
        $expiry = gmdate('Y-m-d H:i:s', time() - (90 * 24 * 60 * 60));

        $wpdb->query($wpdb->prepare(
            "DELETE FROM $table WHERE timestamp < %s",
            $expiry
        ));
    }

    // ==================== INICIALIZACIÓN ====================

    /**
     * Crear tablas necesarias
     */
    public static function init_tables(): void {
        $instance = self::get_instance();
        $instance->init_rate_limit_table();
        $instance->init_audit_table();
    }
}

// Inicializar en plugin load
add_action('plugins_loaded', array('PBS_Security', 'init_tables'));
