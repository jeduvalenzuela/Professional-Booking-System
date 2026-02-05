<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Manejo de notificaciones por email
 */
class PBS_Notifications {

    private static $instance = null;

    /**
     * Singleton
     */
    public static function get_instance() {
        if ( self::$instance === null ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Ajustar headers de email (from name/email) usando filtros de WP
        add_filter( 'wp_mail_from', array( $this, 'filter_mail_from' ) );
        add_filter( 'wp_mail_from_name', array( $this, 'filter_mail_from_name' ) );
    }

    public function filter_mail_from( $email ) {
        $from = get_option( 'pbs_email_from_address', '' );
        if ( ! empty( $from ) && is_email( $from ) ) {
            return $from;
        }
        return $email;
    }

    public function filter_mail_from_name( $name ) {
        $from_name = get_option( 'pbs_email_from_name', '' );
        if ( ! empty( $from_name ) ) {
            return $from_name;
        }
        return $name;
    }

    /**
     * Enviar email de confirmación al cliente
     *
     * @param array $booking
     * @param array $service
     */
    public function send_client_confirmation( $booking, $service ) {
        if ( get_option( 'pbs_email_send_client', '1' ) !== '1' ) {
            return;
        }

        $to      = $booking['email'];
        if ( ! is_email( $to ) ) {
            return;
        }

        $subject = get_option(
            'pbs_email_client_subject',
            __( 'Your appointment is confirmed', 'professional-booking-system' )
        );

        $template = get_option( 'pbs_email_client_template', '' );
        if ( empty( $template ) ) {
            $template = "Hola {{client_name}},\n\nTu turno está confirmado.\n\nServicio: {{service_name}}\nFecha: {{date}}\nHora: {{time}}\nProfesional: {{site_name}}\n\nEnlace de videollamada: {{video_link}}\n\nGracias,\n{{site_name}}";
        }

        $body = $this->replace_placeholders( $template, $booking, $service );

        wp_mail( $to, $subject, $body );
    }

    /**
     * Enviar email al admin/profesional
     *
     * @param array $booking
     * @param array $service
     */
    public function send_admin_notification( $booking, $service ) {
        if ( get_option( 'pbs_email_send_admin', '1' ) !== '1' ) {
            return;
        }

        $to = get_option( 'pbs_email_admin_address', get_option( 'admin_email' ) );
        if ( ! is_email( $to ) ) {
            return;
        }

        $subject = get_option(
            'pbs_email_admin_subject',
            __( 'New confirmed booking', 'professional-booking-system' )
        );

        $template = get_option( 'pbs_email_admin_template', '' );
        if ( empty( $template ) ) {
            $template = "Nuevo turno confirmado.\n\nCliente: {{client_name}} ({{client_email}})\nServicio: {{service_name}}\nFecha: {{date}}\nHora: {{time}}\nID de reserva: {{booking_id}}\nVideollamada: {{video_link}}\n\nSitio: {{site_name}}";
        }

        $body = $this->replace_placeholders( $template, $booking, $service );

        wp_mail( $to, $subject, $body );
    }

    /**
     * Reemplazo de placeholders en plantillas
     */
    protected function replace_placeholders( $template, $booking, $service ) {
        $site_name = get_bloginfo( 'name' );

        $replacements = array(
            '{{client_name}}'  => isset( $booking['name'] ) ? $booking['name'] : '',
            '{{client_email}}' => isset( $booking['email'] ) ? $booking['email'] : '',
            '{{service_name}}' => isset( $service['name'] ) ? $service['name'] : '',
            '{{date}}'         => isset( $booking['date'] ) ? $booking['date'] : '',
            '{{time}}'         => isset( $booking['time'] ) ? $booking['time'] : '',
            '{{video_link}}'   => ! empty( $booking['videocall_link'] ) ? $booking['videocall_link'] : '',
            '{{booking_id}}'   => isset( $booking['id'] ) ? $booking['id'] : '',
            '{{site_name}}'    => $site_name,
        );

        return strtr( $template, $replacements );
    }
}