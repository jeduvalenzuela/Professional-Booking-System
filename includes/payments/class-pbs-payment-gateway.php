<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Clase base para pasarelas de pago
 */
abstract class PBS_Payment_Gateway {

    /**
     * ID interno de la pasarela
     * @return string
     */
    abstract public function get_id();

    /**
     * Nombre
     * @return string
     */
    abstract public function get_title();

    /**
     * ¿Está habilitada?
     * @return bool
     */
    abstract public function is_enabled();

    /**
     * Crear pago / preferencia
     *
     * @param array $booking Datos de la reserva
     * @return array ['success' => bool, 'redirect_url' => string, 'error' => string]
     */
    abstract public function create_payment( $booking );

    /**
     * Procesar webhook
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    abstract public function handle_webhook( WP_REST_Request $request );
}