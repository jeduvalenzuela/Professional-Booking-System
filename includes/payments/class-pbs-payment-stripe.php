<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Pasarela Stripe
 */
class PBS_Payment_Stripe extends PBS_Payment_Gateway {

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
        return 'stripe';
    }

    public function get_title() {
        return __( 'Stripe', 'professional-booking-system' );
    }

    public function is_enabled() {
        return get_option( 'pbs_payment_provider', 'disabled' ) === 'stripe'
            && get_option( 'pbs_stripe_secret_key', '' ) !== '';
    }

    /**
     * Crear sesión de pago de Stripe Checkout
     *
     * @param array $booking
     * @return array ['success' => bool, 'session_id' => string, 'public_key' => string, 'error' => string]
     */
    public function create_payment( $booking ) {
        $secret_key  = get_option( 'pbs_stripe_secret_key', '' );
        $public_key  = get_option( 'pbs_stripe_public_key', '' );
        $currency    = strtolower( get_option( 'pbs_payment_currency', 'ars' ) ); // Stripe usa minúsculas
        $mode        = get_option( 'pbs_stripe_mode', 'test' );

        if ( empty( $secret_key ) || empty( $public_key ) ) {
            return array(
                'success' => false,
                'error'   => 'Stripe keys not configured',
            );
        }

        // Servicio
        $service = PBS_Services::get_instance()->get_service( $booking['service_id'] );
        if ( ! $service ) {
            return array(
                'success' => false,
                'error'   => 'Service not found',
            );
        }

        $amount = floatval( $service['price'] );
        // Stripe trabaja en centavos
        $unit_amount = (int) round( $amount * 100 );

        // URLs de éxito / cancel
        $success_url = add_query_arg(
            array(
                'pbs_stripe_status' => 'success',
                'booking_id'        => $booking['id'],
            ),
            home_url( '/' )
        );

        $cancel_url = add_query_arg(
            array(
                'pbs_stripe_status' => 'cancel',
                'booking_id'        => $booking['id'],
            ),
            home_url( '/' )
        );

        $body = array(
            'mode'               => 'payment',
            'payment_method_types' => array( 'card' ),
            'line_items'         => array(
                array(
                    'price_data' => array(
                        'currency'     => $currency,
                        'product_data' => array(
                            'name' => $service['name'],
                        ),
                        'unit_amount'  => $unit_amount,
                    ),
                    'quantity' => 1,
                ),
            ),
            'customer_email'     => $booking['customer_email'],
            'success_url'        => $success_url,
            'cancel_url'         => $cancel_url,
            'metadata'           => array(
                'booking_id' => $booking['id'],
            ),
        );

        $response = wp_remote_post(
            'https://api.stripe.com/v1/checkout/sessions',
            array(
                'headers' => array(
                    'Authorization' => 'Bearer ' . $secret_key,
                ),
                'body'    => $body, // Stripe acepta form-encoded por defecto
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

        if ( $code >= 200 && $code < 300 && ! empty( $data['id'] ) ) {
            return array(
                'success'    => true,
                'session_id' => $data['id'],
                'public_key' => $public_key,
            );
        }

        $error_message = isset( $data['error']['message'] ) ? $data['error']['message'] : 'Unknown error';

        return array(
            'success' => false,
            'error'   => $error_message,
        );
    }

    /**
     * Webhook de Stripe
     *
     * IMPORTANTE: en producción deberías verificar la firma con STRIPE_WEBHOOK_SECRET.
     */
    public function handle_webhook( WP_REST_Request $request ) {
        $payload = $request->get_body();
        $event   = json_decode( $payload, true );

        if ( empty( $event['type'] ) ) {
            return new WP_REST_Response( array( 'message' => 'Invalid payload' ), 400 );
        }

        // Manejar eventos relevantes
        if ( $event['type'] === 'checkout.session.completed' ) {
            $session = $event['data']['object'];

            if ( ! empty( $session['metadata']['booking_id'] ) ) {
                $booking_id = intval( $session['metadata']['booking_id'] );

                PBS_Bookings::get_instance()->update_payment_status( $booking_id, 'paid' );
                PBS_Bookings::get_instance()->update_booking_status( $booking_id, 'confirmed' );
            }
        }

        // Podrías manejar payment_intent.payment_failed, etc.

        return new WP_REST_Response( array( 'message' => 'OK' ), 200 );
    }
}