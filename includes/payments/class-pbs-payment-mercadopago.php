<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Pasarela MercadoPago Argentina
 */
class PBS_Payment_MercadoPago extends PBS_Payment_Gateway {

    /**
     * Singleton
     */
    private static $instance = null;

    public static function get_instance() {
        if ( self::$instance === null ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function get_id() {
        return 'mercadopago';
    }

    public function get_title() {
        return __( 'MercadoPago', 'professional-booking-system' );
    }

    public function is_enabled() {
        return get_option( 'pbs_payment_provider', 'disabled' ) === 'mercadopago'
            && get_option( 'pbs_mercadopago_access_token', '' ) !== '';
    }

    /**
     * Crear preferencia de pago
     */
    public function create_payment( $booking ) {
        $access_token = get_option( 'pbs_mercadopago_access_token', '' );
        $currency     = get_option( 'pbs_payment_currency', 'ARS' );

        if ( empty( $access_token ) ) {
            return array(
                'success' => false,
                'error'   => 'MercadoPago access token not configured',
            );
        }

        // Obtener info del servicio para armar el item
        $service = PBS_Services::get_service( $booking['service_id'] );
        if ( ! $service ) {
            return array(
                'success' => false,
                'error'   => 'Service not found',
            );
        }

        $amount = floatval( $service['price'] );

        // URLs de retorno (ajústalas si quieres páginas específicas)
        $success_url = add_query_arg(
            array(
                'pbs_mp_status'  => 'success',
                'booking_id'     => $booking['id'],
            ),
            home_url( '/' )
        );

        $failure_url = add_query_arg(
            array(
                'pbs_mp_status' => 'failure',
                'booking_id'    => $booking['id'],
            ),
            home_url( '/' )
        );

        $pending_url = add_query_arg(
            array(
                'pbs_mp_status' => 'pending',
                'booking_id'    => $booking['id'],
            ),
            home_url( '/' )
        );

        $webhook_url = rest_url( 'professional-booking-system/v1/payments/mercadopago/webhook' );

        $body = array(
            'items' => array(
                array(
                    'title'       => $service['name'],
                    'quantity'    => 1,
                    'currency_id' => $currency,
                    'unit_price'  => $amount,
                ),
            ),
            'payer' => array(
                'name'   => $booking['customer_name'],
                'email'  => $booking['customer_email'],
            ),
            'external_reference' => (string) $booking['id'],
            'back_urls' => array(
                'success' => $success_url,
                'failure' => $failure_url,
                'pending' => $pending_url,
            ),
            'auto_return' => 'approved',
            'notification_url' => $webhook_url,
        );

        $response = wp_remote_post(
            'https://api.mercadopago.com/checkout/preferences',
            array(
                'headers' => array(
                    'Authorization' => 'Bearer ' . $access_token,
                    'Content-Type'  => 'application/json',
                ),
                'body'    => wp_json_encode( $body ),
                'timeout' => 30,
            )
        );

        if ( is_wp_error( $response ) ) {
            return array(
                'success' => false,
                'error'   => $response->get_error_message(),
            );
        }

        $code = wp_remote_retrieve_response_code( $response );
        $data = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( $code >= 200 && $code < 300 && ! empty( $data['init_point'] ) ) {
            return array(
                'success'      => true,
                'redirect_url' => $data['init_point'],
            );
        }

        $error_message = isset( $data['message'] ) ? $data['message'] : 'Unknown error';

        return array(
            'success' => false,
            'error'   => $error_message,
        );
    }

    /**
     * Webhook de MercadoPago
     */
    public function handle_webhook( WP_REST_Request $request ) {
        $access_token = get_option( 'pbs_mercadopago_access_token', '' );
        if ( empty( $access_token ) ) {
            return new WP_REST_Response( array( 'message' => 'MP not configured' ), 400 );
        }

        $topic = $request->get_param( 'topic' );
        $id    = $request->get_param( 'id' );

        if ( $topic !== 'payment' || empty( $id ) ) {
            return new WP_REST_Response( array( 'message' => 'Invalid webhook' ), 400 );
        }

        // Consultar detalle del pago
        $response = wp_remote_get(
            'https://api.mercadopago.com/v1/payments/' . intval( $id ),
            array(
                'headers' => array(
                    'Authorization' => 'Bearer ' . $access_token,
                ),
                'timeout' => 30,
            )
        );

        if ( is_wp_error( $response ) ) {
            return new WP_REST_Response( array( 'message' => 'Error fetching payment' ), 500 );
        }

        $code = wp_remote_retrieve_response_code( $response );
        $data = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( $code < 200 || $code >= 300 || empty( $data ) ) {
            return new WP_REST_Response( array( 'message' => 'Invalid payment response' ), 500 );
        }

        // El external_reference es nuestro booking_id
        $booking_id = ! empty( $data['external_reference'] ) ? intval( $data['external_reference'] ) : 0;
        if ( ! $booking_id ) {
            return new WP_REST_Response( array( 'message' => 'No booking reference' ), 400 );
        }

        // Estados de MP: approved, pending, rejected, etc.
        $status = isset( $data['status'] ) ? $data['status'] : '';

        if ( $status === 'approved' ) {
            PBS_Bookings::update_payment_status( $booking_id, 'paid' );
            PBS_Bookings::update_booking_status( $booking_id, 'confirmed' );
        } elseif ( $payment_status === 'failure' ) {
            PBS_Bookings::update_payment_status( $booking_id, 'failed' );
        } else {
            PBS_Bookings::update_payment_status( $booking_id, 'pending' );
        }

        return new WP_REST_Response( array( 'message' => 'OK' ), 200 );
    }
}