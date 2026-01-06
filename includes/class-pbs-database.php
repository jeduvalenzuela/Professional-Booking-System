<?php
/**
 * Clase para gestión de base de datos
 *
 * @package Professional_Booking_System
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

class PBS_Database {
    
    /**
     * Crear todas las tablas necesarias
     */
    public static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        // Tabla de servicios
        $table_services = $wpdb->prefix . 'pbs_services';
        $sql_services = "CREATE TABLE $table_services (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            description text,
            duration int(11) NOT NULL DEFAULT 60,
            price decimal(10,2) NOT NULL DEFAULT 0.00,
            enable_videocall tinyint(1) NOT NULL DEFAULT 0,
            category varchar(100),
            status varchar(20) NOT NULL DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY status (status)
        ) $charset_collate;";
        
        // Tabla de horarios disponibles
        $table_schedules = $wpdb->prefix . 'pbs_schedules';
        $sql_schedules = "CREATE TABLE $table_schedules (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            day_of_week tinyint(1) NOT NULL COMMENT '0=Domingo, 1=Lunes, ..., 6=Sábado',
            start_time time NOT NULL,
            end_time time NOT NULL,
            is_active tinyint(1) NOT NULL DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY day_of_week (day_of_week),
            KEY is_active (is_active)
        ) $charset_collate;";
        
        // Tabla de excepciones (días bloqueados, vacaciones, etc.)
        $table_exceptions = $wpdb->prefix . 'pbs_exceptions';
        $sql_exceptions = "CREATE TABLE $table_exceptions (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            exception_date date NOT NULL,
            start_time time,
            end_time time,
            type varchar(50) NOT NULL DEFAULT 'blocked' COMMENT 'blocked, vacation, holiday',
            reason text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY exception_date (exception_date),
            KEY type (type)
        ) $charset_collate;";
        
        // Tabla de reservas
        $table_bookings = $wpdb->prefix . 'pbs_bookings';
        $sql_bookings = "CREATE TABLE $table_bookings (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            service_id bigint(20) UNSIGNED NOT NULL,
            customer_name varchar(255) NOT NULL,
            customer_email varchar(255) NOT NULL,
            customer_phone varchar(50),
            booking_date date NOT NULL,
            booking_time time NOT NULL,
            duration int(11) NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'pending' COMMENT 'pending, confirmed, completed, cancelled',
            payment_status varchar(20) NOT NULL DEFAULT 'pending' COMMENT 'pending, paid, refunded',
            payment_amount decimal(10,2) NOT NULL DEFAULT 0.00,
            payment_id varchar(255),
            videocall_link text,
            google_event_id varchar(255),
            customer_notes text,
            admin_notes text,
            cancellation_token varchar(64),
            cancelled_at datetime,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY service_id (service_id),
            KEY booking_date (booking_date),
            KEY status (status),
            KEY payment_status (payment_status),
            KEY customer_email (customer_email),
            KEY cancellation_token (cancellation_token)
        ) $charset_collate;";
        
        // Tabla de configuración del profesional
        $table_config = $wpdb->prefix . 'pbs_config';
        $sql_config = "CREATE TABLE $table_config (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            config_key varchar(100) NOT NULL,
            config_value longtext,
            autoload varchar(20) NOT NULL DEFAULT 'yes',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY config_key (config_key)
        ) $charset_collate;";
        
        // Tabla de notificaciones/emails enviados
        $table_notifications = $wpdb->prefix . 'pbs_notifications';
        $sql_notifications = "CREATE TABLE $table_notifications (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            booking_id bigint(20) UNSIGNED NOT NULL,
            notification_type varchar(50) NOT NULL COMMENT 'confirmation, reminder_24h, reminder_2h, cancellation',
            sent_at datetime DEFAULT CURRENT_TIMESTAMP,
            status varchar(20) NOT NULL DEFAULT 'sent' COMMENT 'sent, failed',
            PRIMARY KEY  (id),
            KEY booking_id (booking_id),
            KEY notification_type (notification_type)
        ) $charset_collate;";
        
        // Tabla de bloqueos temporales (prevenir doble reserva durante pago)
        $table_locks = $wpdb->prefix . 'pbs_booking_locks';
        $sql_locks = "CREATE TABLE $table_locks (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            booking_date date NOT NULL,
            booking_time time NOT NULL,
            service_id bigint(20) UNSIGNED NOT NULL,
            session_id varchar(255) NOT NULL,
            expires_at datetime NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY booking_datetime (booking_date, booking_time),
            KEY expires_at (expires_at),
            KEY session_id (session_id)
        ) $charset_collate;";
        
        // Ejecutar creación de tablas
        dbDelta($sql_services);
        dbDelta($sql_schedules);
        dbDelta($sql_exceptions);
        dbDelta($sql_bookings);
        dbDelta($sql_config);
        dbDelta($sql_notifications);
        dbDelta($sql_locks);
        
        // Guardar versión de la base de datos
        update_option('pbs_db_version', PBS_VERSION);
        
        // Insertar datos de ejemplo (opcional)
        self::insert_sample_data();
    }
    
    /**
     * Insertar datos de ejemplo
     */
    private static function insert_sample_data() {
        global $wpdb;
        
        $table_services = $wpdb->prefix . 'pbs_services';
        $table_schedules = $wpdb->prefix . 'pbs_schedules';
        
        // Verificar si ya hay datos
        $existing_services = $wpdb->get_var("SELECT COUNT(*) FROM $table_services");
        
        if ($existing_services == 0) {
            // Insertar servicio de ejemplo
            $wpdb->insert(
                $table_services,
                array(
                    'name' => __('Consulta General', 'professional-booking-system'),
                    'description' => __('Consulta general de 60 minutos', 'professional-booking-system'),
                    'duration' => 60,
                    'price' => 50.00,
                    'enable_videocall' => 1,
                    'status' => 'active'
                ),
                array('%s', '%s', '%d', '%f', '%d', '%s')
            );
        }
        
        // Verificar si ya hay horarios
        $existing_schedules = $wpdb->get_var("SELECT COUNT(*) FROM $table_schedules");
        
        if ($existing_schedules == 0) {
            // Insertar horarios de ejemplo (Lunes a Viernes, 9:00-13:00 y 15:00-19:00)
            $default_schedules = array(
                array('day' => 1, 'start' => '09:00:00', 'end' => '13:00:00'), // Lunes mañana
                array('day' => 1, 'start' => '15:00:00', 'end' => '19:00:00'), // Lunes tarde
                array('day' => 2, 'start' => '09:00:00', 'end' => '13:00:00'), // Martes mañana
                array('day' => 2, 'start' => '15:00:00', 'end' => '19:00:00'), // Martes tarde
                array('day' => 3, 'start' => '09:00:00', 'end' => '13:00:00'), // Miércoles mañana
                array('day' => 3, 'start' => '15:00:00', 'end' => '19:00:00'), // Miércoles tarde
                array('day' => 4, 'start' => '09:00:00', 'end' => '13:00:00'), // Jueves mañana
                array('day' => 4, 'start' => '15:00:00', 'end' => '19:00:00'), // Jueves tarde
                array('day' => 5, 'start' => '09:00:00', 'end' => '13:00:00'), // Viernes mañana
                array('day' => 5, 'start' => '15:00:00', 'end' => '19:00:00'), // Viernes tarde
            );
            
            foreach ($default_schedules as $schedule) {
                $wpdb->insert(
                    $table_schedules,
                    array(
                        'day_of_week' => $schedule['day'],
                        'start_time' => $schedule['start'],
                        'end_time' => $schedule['end'],
                        'is_active' => 1
                    ),
                    array('%d', '%s', '%s', '%d')
                );
            }
        }
    }
    
    /**
     * Eliminar todas las tablas (usar con precaución)
     */
    public static function drop_tables() {
        global $wpdb;
        
        $tables = array(
            $wpdb->prefix . 'pbs_services',
            $wpdb->prefix . 'pbs_schedules',
            $wpdb->prefix . 'pbs_exceptions',
            $wpdb->prefix . 'pbs_bookings',
            $wpdb->prefix . 'pbs_config',
            $wpdb->prefix . 'pbs_notifications',
            $wpdb->prefix . 'pbs_booking_locks',
        );
        
        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS $table");
        }
        
        delete_option('pbs_db_version');
    }
    
    /**
     * Limpiar bloqueos expirados
     */
    public static function clean_expired_locks() {
        global $wpdb;
        
        $table_locks = $wpdb->prefix . 'pbs_booking_locks';
        
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM $table_locks WHERE expires_at < %s",
                current_time('mysql')
            )
        );
    }
}