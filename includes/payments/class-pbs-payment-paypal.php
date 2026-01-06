<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Pasarela PayPal
 */
class PBS_Payment_PayPal extends PBS_Payment_Gateway {

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
        return 'paypal';
    }

    public function get_title() {
        return __( 'PayPal', 'professional-booking-system' );
    }

    public function is_enabled() {
        return get_option( 'pbs_payment_provider', 'disabled' ) === 'paypal'
            && get_option( 'pbs_paypal_client_id', '' ) !== ''
            && get_option( 'pbs_paypal_secret', '' ) !== '';
    }

    /**
     * Obtener access token de PayPal
     */
    protected function get_access_token() {
        $client_id = get_option( 'pbs_paypal_client_id', '' );
        $secret    = get_option( 'pbs_paypal_secret', '' );
        $mode      = get_option( 'pbs_paypal_mode', 'sandbox' );

        if ( empty( $client_id ) || empty( $secret ) ) {
            return new WP_Error( 'missing_keys', 'PayPal keys not configured' );
        }

        $base_url = ( $mode === 'live' )
            ? 'https://api.paypal.com'
            : 'https://api-m.sandbox.paypal.com';

        $response = wp_remote_post(
            $base_url . '/v1/oauth2/token',
            array(
                'headers' => array(
                    'Authorization' => 'Basic ' . base64_encode( $client_id . ':' . $secret ),
                ),
                'body'    => array(
                    'grant_type' => 'client_credentials',
                ),
                'timeout' => 30,
            )
        );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code( $response );
        $data = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( $code >= 200 && $code < 300 && ! empty( $data['access_token'] ) ) {
            return $data['access_token'];
        }

        return new WP_Error( 'paypal_auth_error', 'Error getting PayPal access token' );
    }

    /**
     * Crear orden de PayPal
     *
     * @param array $booking
     * @return array ['success' => bool, 'approve_url' => string, 'error' => string]
     */
    public function create_payment( $booking ) {
        $access_token = $this->get_access_token();
        if ( is_wp_error( $access_token ) ) {
            return array(
                'success' => false,
                'error'   => $access_token->get_error_message(),
            );
        }

        $mode     = get_option( 'pbs_paypal_mode', 'sandbox' );
        $currency = strtoupper( get_option( 'pbs_payment_currency', 'ARS' ) );
        $base_url = ( $mode === 'live' )
            ? 'https://api.paypal.com'
            : 'https://api-m.sandbox.paypal.com';

        // Servicio
        $service = PBS_Services::get_instance()->get_service( $booking['service_id'] );
        if ( ! $service ) {
            return array(
                'success' => false,
                'error'   => 'Service not found',
            );
        }

        $amount = floatval( $service['price'] );

        // URLs de retorno
        $return_url = add_query_arg(
            array(
                'pbs_paypal_status' => 'success',
                'booking_id'        => $booking['id'],
            ),
            home_url( '/' )
        );

        $cancel_url = add_query_arg(
            array(
                'pbs_paypal_status' => 'cancel',
                'booking_id'        => $booking['id'],
            ),
            home_url( '/' )
        );

        $body = array(
            'intent' => 'CAPTURE',
            'purchase_units' => array(
                array(
                    'amount' => array(
                        'currency_code' => $currency,
                        'value'         => number_format( $amount, 2, '.', '' ),
                    ),
                    'description' => $service['name'],
                    'custom_id'   => (string) $booking['id'], // referencia de nuestra reserva
                ),
            ),
            'application_context' => array(
                'return_url' => $return_url,
                'cancel_url' => $cancel_url,
            ),
        );

        $response = wp_remote_post(
            $base_url . '/v2/checkout/orders',
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

        if ( $code >= 200 && $code < 300 && ! empty( $data['links'] ) ) {
            $approve_url = '';
            foreach ( $data['links'] as $link ) {
                if ( isset( $link['rel'] ) && $link['rel'] === 'approve' ) {
                    $approve_url = $link['href'];
                    break;
                }
            }

            if ( $approve_url ) {
                return array(
                    'success'     => true,
                    'approve_url' => $approve_url,
                );
            }
        }

        $error_message = isset( $data['message'] ) ? $data['message'] : 'Unknown error';

        return array(
            'success' => false,
            'error'   => $error_message,
        );
    }

    /**
     * Webhook de PayPal
     *
     * IMPORTANTE: en producción deberías verificar la firma del webhook.
     */
    public function handle_webhook( WP_REST_Request $request ) {
        $event = json_decode( $request->get_body(), true );

        if ( empty( $event['event_type'] ) ) {
            return new WP_REST_Response( array( 'message' => 'Invalid payload' ), 400 );
        }

        // Ejemplo: CHECKOUT.ORDER.APPROVED
        if ( $event['event_type'] === 'CHECKOUT.ORDER.APPROVED' ) {
            if ( ! empty( $event['resource']['purchase_units'][0]['custom_id'] ) ) {
                $booking_id = intval( $event['resource']['purchase_units'][0]['custom_id'] );

                PBS_Bookings::get_instance()->update_payment_status( $booking_id, 'paid' );
                PBS_Bookings::get_instance()->update_booking_status( $booking_id, 'confirmed' );
            }
        }

        // Aquí podrías manejar otros eventos relevantes (pago fallido, reembolsos, etc.)

        return new WP_REST_Response( array( 'message' => 'OK' ), 200 );
    }
}