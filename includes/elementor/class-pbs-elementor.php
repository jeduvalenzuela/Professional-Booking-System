<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Integración con Elementor
 */
class PBS_Elementor {

    /**
     * Singleton instance
     */
    private static $instance = null;

    /**
     * Get instance
     */
    public static function get_instance() {
        if ( self::$instance === null ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        add_action( 'elementor/widgets/register', array( $this, 'register_widgets' ) );
        add_action( 'elementor/frontend/after_enqueue_styles', array( $this, 'enqueue_widget_styles' ) );
        add_action( 'elementor/frontend/after_register_scripts', array( $this, 'enqueue_widget_scripts' ) );
    }

    /**
     * Registrar widgets
     */
    public function register_widgets( $widgets_manager ) {
        require_once PBS_PLUGIN_DIR . 'includes/elementor/widgets/class-pbs-booking-widget.php';
        $widgets_manager->register( new \PBS_Booking_Widget() );
    }

    /**
     * Encolar estilos del widget
     */
    public function enqueue_widget_styles() {
        wp_enqueue_style(
            'pbs-booking-widget',
            PBS_PLUGIN_URL . 'assets/css/booking-widget.css',
            array(),
            PBS_VERSION
        );
    }

    /**
     * Encolar scripts del widget
     */
    public function enqueue_widget_scripts() {
        wp_register_script(
            'pbs-booking-widget',
            PBS_PLUGIN_URL . 'assets/js/booking-widget.js',
            array( 'jquery' ),
            PBS_VERSION,
            true
        );

        // Pasar datos al JS
        wp_localize_script(
            'pbs-booking-widget',
            'pbsBooking',
            array(
                'apiUrl'    => rest_url( 'professional-booking-system/v1' ),
                'nonce'     => wp_create_nonce( 'wp_rest' ),
                'csrfToken' => wp_create_nonce( 'pbs_booking_csrf' ),
                'payment'   => array(
                    'provider' => get_option( 'pbs_payment_provider', 'disabled' ),
                    'stripe'     => array(
                        'public_key' => get_option( 'pbs_stripe_public_key', '' ),
                    ),
                ),
                'i18n'      => array(
                    'selectService'   => __( 'Select a service', 'professional-booking-system' ),
                    'selectDate'      => __( 'Select date', 'professional-booking-system' ),
                    'selectTime'      => __( 'Select time', 'professional-booking-system' ),
                    'noSlots'         => __( 'No available slots for this date', 'professional-booking-system' ),
                    'loading'         => __( 'Loading...', 'professional-booking-system' ),
                    'bookingSuccess'  => __( 'Booking created successfully!', 'professional-booking-system' ),
                    'bookingError'    => __( 'Error creating booking. Please try again.', 'professional-booking-system' ),
                    'fillAllFields'   => __( 'Please fill all required fields', 'professional-booking-system' ),
                    'invalidEmail'    => __( 'Please enter a valid email', 'professional-booking-system' ),
                ),
            )
        );
    }
}