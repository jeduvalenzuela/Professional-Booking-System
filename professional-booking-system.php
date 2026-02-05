<?php
/**
 * Plugin Name: Professional Booking System
 * Description: Sistema profesional de gestión de reservas con pagos online, integración con Google Calendar y widgets para Elementor
 * Version: 1.0.0
 * Author: Eduardo valenzuela
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: professional-booking-system
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 8.0
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Definir constantes del plugin
define('PBS_VERSION', '1.0.9');
define('PBS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('PBS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('PBS_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Clase principal del plugin
 */
class Professional_Booking_System {
    
    /**
     * Instancia única del plugin (Singleton)
     */
    private static $instance = null;
    
    /**
     * Obtener instancia única
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
    }
    
    /**
     * Cargar dependencias
     */
    private function load_dependencies() {
        // Cargar clases principales
        require_once PBS_PLUGIN_DIR . 'includes/class-pbs-database.php';
        require_once PBS_PLUGIN_DIR . 'includes/class-pbs-security.php';
        require_once PBS_PLUGIN_DIR . 'includes/class-pbs-tests.php';
        require_once PBS_PLUGIN_DIR . 'includes/class-pbs-admin.php';
        require_once PBS_PLUGIN_DIR . 'includes/class-pbs-bookings.php';
        require_once PBS_PLUGIN_DIR . 'includes/class-pbs-services.php';
        require_once PBS_PLUGIN_DIR . 'includes/class-pbs-schedules.php';
        require_once PBS_PLUGIN_DIR . 'includes/class-pbs-notifications.php';
        
        // Cargar widgets de Elementor
        require_once PBS_PLUGIN_DIR . 'includes/elementor/class-pbs-elementor.php';
        
        // Cargar API REST
        require_once PBS_PLUGIN_DIR . 'includes/api/class-pbs-rest-api.php';

        // Cargar pasarelas de pago
        require_once PBS_PLUGIN_DIR . 'includes/payments/class-pbs-payment-gateway.php';
        require_once PBS_PLUGIN_DIR . 'includes/payments/class-pbs-payment-mercadopago.php';
        require_once PBS_PLUGIN_DIR . 'includes/payments/class-pbs-payment-stripe.php';
        require_once PBS_PLUGIN_DIR . 'includes/payments/class-pbs-payment-paypal.php';

        // Cargar integraciones
        require_once PBS_PLUGIN_DIR . 'includes/integrations/class-pbs-google-calendar.php';
    }
    
    /**
     * Inicializar hooks
     */
    private function init_hooks() {
        // Hook de activación
        register_activation_hook(__FILE__, array($this, 'activate'));
        
        // Hook de desactivación
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // Inicializar componentes
        add_action('plugins_loaded', array($this, 'init'));
        
        // Cargar traducciones
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        
        // Encolar scripts y estilos
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }
    
    /**
     * Activación del plugin
     */
    public function activate() {
        // Crear tablas de base de datos
        PBS_Database::create_tables();
        
        // Crear opciones por defecto
        $this->create_default_options();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Desactivación del plugin
     */
    public function deactivate() {
        // Limpiar cron jobs
        wp_clear_scheduled_hook('pbs_send_reminders');
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Crear opciones por defecto
     */
    private function create_default_options() {
        $defaults = array(
            'pbs_professional_name' => get_bloginfo('name'),
            'pbs_professional_specialty' => '',
            'pbs_default_duration' => 60,
            'pbs_buffer_time' => 0,
            'pbs_min_booking_notice' => 1,
            'pbs_max_booking_notice' => 90,
            'pbs_currency' => 'USD',
            'pbs_require_payment' => 'full',
            'pbs_payment_percentage' => 50,
            'pbs_enable_videocalls' => 'no',
            'pbs_cancellation_hours' => 24,
            'pbs_timezone' => wp_timezone_string(),
            'pbs_reminder_24h' => 'yes',
            'pbs_reminder_2h' => 'no',
        );
        
        foreach ($defaults as $key => $value) {
            if (get_option($key) === false) {
                add_option($key, $value);
            }
        }
    }
    
    /**
     * Inicializar componentes
     */
    public function init() {
        // Inicializar admin
        if (is_admin()) {
            PBS_Admin::get_instance();
        }
        
        // Inicializar widgets de Elementor
        PBS_Elementor::get_instance();
        
        // Inicializar API REST
        PBS_REST_API::get_instance();
        
        // Programar cron jobs si no están programados
        if (!wp_next_scheduled('pbs_send_reminders')) {
            wp_schedule_event(time(), 'hourly', 'pbs_send_reminders');
        }

        PBS_Payment_MercadoPago::get_instance();
        PBS_Payment_Stripe::get_instance();
        PBS_Payment_PayPal::get_instance();

        PBS_Notifications::get_instance();
    }
    
    /**
     * Cargar traducciones
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'professional-booking-system',
            false,
            dirname(PBS_PLUGIN_BASENAME) . '/languages'
        );
    }
    
    /**
     * Encolar assets del frontend
     */
    public function enqueue_frontend_assets() {
        // CSS
        wp_enqueue_style(
            'pbs-frontend',
            PBS_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            PBS_VERSION
        );
        
        // JavaScript
        wp_enqueue_script(
            'pbs-frontend',
            PBS_PLUGIN_URL . 'assets/js/frontend.js',
            array('jquery'),
            PBS_VERSION,
            true
        );
        
        // Localizar script
        wp_localize_script('pbs-frontend', 'pbsData', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'restUrl' => rest_url('professional-booking-system/v1/'),
            'nonce' => wp_create_nonce('pbs_nonce'),
            'currency' => get_option('pbs_currency', 'USD'),
            'timezone' => wp_timezone_string(),
        ));
    }
    
    /**
     * Encolar assets del admin
     */
    public function enqueue_admin_assets($hook) {
        // Solo cargar en páginas del plugin
        if (strpos($hook, 'professional-booking') === false) {
            return;
        }
        
        // CSS
        wp_enqueue_style(
            'pbs-admin',
            PBS_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            PBS_VERSION
        );
        
        // JavaScript
        wp_enqueue_script(
            'pbs-admin',
            PBS_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery', 'jquery-ui-datepicker'),
            PBS_VERSION,
            true
        );
        
        // Localizar script
        wp_localize_script('pbs-admin', 'pbsAdminData', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('pbs_admin_nonce'),
        ));
    }
}

/**
 * Inicializar el plugin
 */
function pbs_init() {
    return Professional_Booking_System::get_instance();
}

// Wrapper AJAX handler: delega a PBS_Admin cuando exista
add_action( 'wp_ajax_pbs_get_booking_detail', 'pbs_ajax_get_booking_detail' );
add_action( 'wp_ajax_nopriv_pbs_get_booking_detail', 'pbs_ajax_get_booking_detail' );
function pbs_ajax_get_booking_detail() {
    if ( ! class_exists( 'PBS_Admin' ) ) {
        return;
    }
    $admin = PBS_Admin::get_instance();
    if ( method_exists( $admin, 'ajax_get_booking_detail' ) ) {
        $admin->ajax_get_booking_detail();
    }
}

// Iniciar el plugin
pbs_init();