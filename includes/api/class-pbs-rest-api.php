<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * REST API para Professional Booking System
 */
class PBS_REST_API {

    /**
     * Singleton instance
     * @var PBS_REST_API|null
     */
    private static $instance = null;

    /**
     * Get instance
     * @return PBS_REST_API
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
        add_action( 'rest_api_init', array( $this, 'register_routes' ) );
    }

    /**
     * Registrar rutas REST
     */
    public function register_routes() {
        $namespace = 'professional-booking-system/v1';

        // Servicios (público)
        register_rest_route(
            $namespace,
            '/services',
            array(
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array( $this, 'get_services' ),
                    'permission_callback' => '__return_true',
                ),
            )
        );

        register_rest_route(
            $namespace,
            '/services/(?P<id>\d+)',
            array(
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array( $this, 'get_service' ),
                    'permission_callback' => '__return_true',
                ),
            )
        );

        // Disponibilidad por día (público)
        register_rest_route(
            $namespace,
            '/availability/day',
            array(
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array( $this, 'get_day_availability' ),
                    'permission_callback' => '__return_true',
                    'args'                => array(
                        'service_id' => array(
                            'required' => true,
                            'type'     => 'integer',
                        ),
                        'date' => array(
                            'required' => true,
                            'type'     => 'string', // formato Y-m-d
                        ),
                    ),
                ),
            )
        );

        // Crear reserva (público)
        register_rest_route(
            $namespace,
            '/bookings/create',
            array(
                array(
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => array( $this, 'create_booking' ),
                    'permission_callback' => '__return_true',
                    'args'                => array(
                        'service_id' => array(
                            'required' => true,
                            'type'     => 'integer',
                        ),
                        'name' => array(
                            'required' => true,
                            'type'     => 'string',
                        ),
                        'email' => array(
                            'required' => true,
                            'type'     => 'string',
                        ),
                        'phone' => array(
                            'required' => false,
                            'type'     => 'string',
                        ),
                        'date' => array(
                            'required' => true,
                            'type'     => 'string', // Y-m-d
                        ),
                        'time' => array(
                            'required' => true,
                            'type'     => 'string', // H:i
                        ),
                        'notes' => array(
                            'required' => false,
                            'type'     => 'string',
                        ),
                    ),
                ),
            )
        );

        // MercadoPago - crear preferencia
        register_rest_route(
            $namespace,
            '/payments/mercadopago/create_preference',
            array(
                array(
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => array( $this, 'mercadopago_create_preference' ),
                    'permission_callback' => '__return_true',
                ),
            )
        );

        // MercadoPago - webhook
        register_rest_route(
            $namespace,
            '/payments/mercadopago/webhook',
            array(
                array(
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => array( $this, 'mercadopago_webhook' ),
                    'permission_callback' => '__return_true',
                ),
            )
        );

        // Stripe - crear sesión de checkout
        register_rest_route(
            $namespace,
            '/payments/stripe/create_session',
            array(
                array(
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => array( $this, 'stripe_create_session' ),
                    'permission_callback' => '__return_true',
                ),
            )
        );

        // Stripe - webhook
        register_rest_route(
            $namespace,
            '/payments/stripe/webhook',
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( $this, 'stripe_webhook' ),
                'permission_callback' => '__return_true',
            )
        );

        // PayPal - crear orden
        register_rest_route(
            $namespace,
            '/payments/paypal/create_order',
            array(
                array(
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => array( $this, 'paypal_create_order' ),
                    'permission_callback' => '__return_true',
                ),
            )
        );

        // PayPal - webhook
        register_rest_route(
            $namespace,
            '/payments/paypal/webhook',
            array(
                array(
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => array( $this, 'paypal_webhook' ),
                    'permission_callback' => '__return_true',
                ),
            )
        );
    }

    /**
     * GET /services
     */
    public function get_services( WP_REST_Request $request ) {
        $services = PBS_Services::get_all( array( 'status' => 'active' ) );

        $data = array();
        foreach ( $services as $service ) {
            $data[] = array(
                'id'          => (int) $service->id,
                'name'        => $service->name,
                'description' => $service->description,
                'duration'    => (int) $service->duration,
                'price'       => (float) $service->price,
                'currency'    => isset( $service->currency ) ? $service->currency : get_option( 'pbs_payment_currency', 'USD' ),
                'max_per_slot'=> isset( $service->max_per_slot ) ? (int) $service->max_per_slot : 1,
            );
        }

        return new WP_REST_Response( $data, 200 );
    }

    /**
     * GET /services/{id}
     */
    public function get_service( WP_REST_Request $request ) {
        $id = (int) $request['id'];
        $service = PBS_Services::get( $id );

        if ( ! $service ) {
            return new WP_REST_Response(
                array( 'message' => __( 'Service not found', 'professional-booking-system' ) ),
                404
            );
        }

        if ( $service['status'] !== 'active' ) {
            return new WP_REST_Response(
                array( 'message' => __( 'Service not available', 'professional-booking-system' ) ),
                403
            );
        }

        $data = array(
            'id'          => (int) $service->id,
            'name'        => $service->name,
            'description' => $service->description,
            'duration'    => (int) $service->duration,
            'price'       => (float) $service->price,
            'currency'    => isset( $service->currency ) ? $service->currency : get_option( 'pbs_payment_currency', 'USD' ),
            'max_per_slot'=> isset( $service->max_per_slot ) ? (int) $service->max_per_slot : 1,
        );

        return new WP_REST_Response( $data, 200 );
    }

    /**
     * GET /availability/day
     * Params: service_id, date (Y-m-d)
     */
    public function get_day_availability( WP_REST_Request $request ) {
        $service_id = (int) $request->get_param( 'service_id' );
        $date       = sanitize_text_field( $request->get_param( 'date' ) );

        // Validar formato de fecha básico
        $dt = date_create_from_format( 'Y-m-d', $date );
        if ( ! $dt || $dt->format( 'Y-m-d' ) !== $date ) {
            return new WP_REST_Response(
                array( 'message' => __( 'Invalid date format. Use Y-m-d.', 'professional-booking-system' ) ),
                400
            );
        }

        // Comprobar que el servicio existe y está activo
        $service = PBS_Services::get( $service_id );
        if ( ! $service || $service['status'] !== 'active' ) {
            return new WP_REST_Response(
                array( 'message' => __( 'Service not available', 'professional-booking-system' ) ),
                404
            );
        }

        $duration = (int) $service['duration'];
        if ( $duration <= 0 ) {
            $duration = 60; // fallback
        }

        // Obtener horarios para ese día
        $weekday = strtolower( date( 'l', strtotime( $date ) ) ); // monday, tuesday, ...
        $schedules = PBS_Schedules::get_schedules_by_day( $weekday );

        // Aplicar excepciones (día bloqueado completo)
        $is_blocked = PBS_Schedules::get_instance()->is_day_blocked( $date );
        if ( $is_blocked ) {
            return new WP_REST_Response(
                array(
                    'date'  => $date,
                    'slots' => array(),
                ),
                200
            );
        }

        // Para simplificar, asumimos una sola franja por día para el MVP.
        // Si hay varias filas en pbs_schedules, se concatenan todas las franjas.
        $slots = array();

        foreach ( $schedules as $schedule ) {
            $start_time = $schedule['start_time']; // "09:00"
            $end_time   = $schedule['end_time'];   // "18:00"

            $current = strtotime( $date . ' ' . $start_time );
            $end     = strtotime( $date . ' ' . $end_time );

            while ( $current + ( $duration * 60 ) <= $end ) {
                $slot_start = date( 'H:i', $current );
                $slot_end   = date( 'H:i', $current + ( $duration * 60 ) );

                // Comprobar si el slot está libre
                $is_taken = PBS_Bookings::get_instance()->is_slot_taken(
                    $service_id,
                    $date,
                    $slot_start,
                    $slot_end
                );

                if ( ! $is_taken ) {
                    $slots[] = array(
                        'start' => $slot_start,
                        'end'   => $slot_end,
                    );
                }

                $current += $duration * 60;
            }
        }

        // --- Integración con Google Calendar para bloquear slots ocupados ---
        if ( PBS_Google_Calendar::get_instance()->is_enabled() && ! empty( $slots ) ) {

            // Tomamos rango desde el primer slot hasta el último slot del día
            $first_slot = reset( $slots );
            $last_slot  = end(   $slots );

            // Creamos un rango un poco más amplio (por seguridad)
            $start_datetime = $date . 'T00:00:00';
            $end_datetime   = $date . 'T23:59:59';

            $busy_events = PBS_Google_Calendar::get_instance()->get_busy_events( $start_datetime, $end_datetime );

            if ( ! is_wp_error( $busy_events ) && ! empty( $busy_events ) ) {

                // Convertimos slots a minutos desde medianoche para comparar fácil
                $slots_in_minutes = array();
                foreach ( $slots as $index => $slot_item ) {
                    list( $h, $m ) = explode( ':', $slot_item['start'] );
                    $slots_in_minutes[ $index ] = intval( $h ) * 60 + intval( $m );
                }

                $filtered_slots = array();

                foreach ( $slots as $index => $slot_item ) {
                    // Duración del servicio (para saber cuánto ocupa el slot)
                    $duration = isset( $service->duration ) ? (int) $service->duration : 60;

                    $slot_start_ts = strtotime( $date . ' ' . $slot_item['start'] . ':00' );
                    $slot_end_ts   = $slot_start_ts + ( $duration * 60 );

                    $overlaps = false;

                    foreach ( $busy_events as $event ) {
                        $event_start_ts = strtotime( $event['start'] );
                        $event_end_ts   = strtotime( $event['end'] );

                        // Comprobamos solapamiento: start < other_end && end > other_start
                        if ( $slot_start_ts < $event_end_ts && $slot_end_ts > $event_start_ts ) {
                            $overlaps = true;
                            break;
                        }
                    }

                    if ( ! $overlaps ) {
                        $filtered_slots[] = $slot_item;
                    }
                }

                $slots = $filtered_slots;
            }
        }

        return new WP_REST_Response(
            array(
                'date'  => $date,
                'slots' => $slots,
            ),
            200
        );
    }

    /**
     * POST /bookings/create
     */
    public function create_booking( WP_REST_Request $request ) {
        $params = $request->get_json_params();
        if ( empty( $params ) ) {
            $params = $request->get_params(); // por si viene como form-data
        }

        $service_id = isset( $params['service_id'] ) ? (int) $params['service_id'] : 0;
        $name       = isset( $params['name'] ) ? sanitize_text_field( $params['name'] ) : '';
        $email      = isset( $params['email'] ) ? sanitize_email( $params['email'] ) : '';
        $phone      = isset( $params['phone'] ) ? sanitize_text_field( $params['phone'] ) : '';
        $date       = isset( $params['date'] ) ? sanitize_text_field( $params['date'] ) : '';
        $time       = isset( $params['time'] ) ? sanitize_text_field( $params['time'] ) : '';
        $notes      = isset( $params['notes'] ) ? sanitize_textarea_field( $params['notes'] ) : '';

        // Validaciones básicas
        if ( ! $service_id || ! $name || ! $email || ! $date || ! $time ) {
            return new WP_REST_Response(
                array( 'message' => __( 'Missing required fields', 'professional-booking-system' ) ),
                400
            );
        }

        if ( ! is_email( $email ) ) {
            return new WP_REST_Response(
                array( 'message' => __( 'Invalid email', 'professional-booking-system' ) ),
                400
            );
        }

        // Validar fecha/hora
        $dt = date_create_from_format( 'Y-m-d H:i', $date . ' ' . $time );
        if ( ! $dt || $dt->format( 'Y-m-d H:i' ) !== $date . ' ' . $time ) {
            return new WP_REST_Response(
                array( 'message' => __( 'Invalid date or time format', 'professional-booking-system' ) ),
                400
            );
        }

        // Obtener servicio
        $service = PBS_Services::get_instance()->get_service( $service_id );
        if ( ! $service || $service['status'] !== 'active' ) {
            return new WP_REST_Response(
                array( 'message' => __( 'Service not available', 'professional-booking-system' ) ),
                404
            );
        }

        $duration   = (int) $service['duration'];
        $end_time   = date( 'H:i', strtotime( $time ) + ( $duration * 60 ) );

        // Comprobar que el slot sigue libre
        $is_taken = PBS_Bookings::is_slot_taken(
            $service_id,
            $date,
            $time
        );

        if ( $is_taken ) {
            return new WP_REST_Response(
                array( 'message' => __( 'Selected slot is no longer available', 'professional-booking-system' ) ),
                409
            );
        }

        // En PBS_REST_API::create_booking() justo antes de crear la reserva
        if ( PBS_Google_Calendar::get_instance()->is_enabled() ) {
            $gcal = PBS_Google_Calendar::get_instance();

            $service = PBS_Services::get_instance()->get_service( $service_id );
            $duration = isset( $service['duration'] ) ? (int) $service['duration'] : 60;

            $start_datetime = $date . 'T' . $time . ':00';
            $start_ts = strtotime( $start_datetime );
            $end_ts   = $start_ts + ( $duration * 60 );
            $end_datetime = date( 'Y-m-d\TH:i:s', $end_ts );

            $busy_events = $gcal->get_busy_events( $start_datetime, $end_datetime );

            if ( ! is_wp_error( $busy_events ) && ! empty( $busy_events ) ) {
                // Si hay algún evento que solape, devolvemos error
                return new WP_REST_Response(
                    array(
                        'message' => 'Selected time is no longer available',
                        'code'    => 'slot_taken_google',
                    ),
                    409
                );
            }
        }

        // Crear reserva (usando método ya existente)
        $create_result = PBS_Bookings::create_booking(
            array(
                'service_id' => $service_id,
                'customer_name' => $name,
                'customer_email' => $email,
                'customer_phone' => $phone,
                'booking_date' => $date,
                'booking_time' => $time,
                'customer_notes' => $notes,
            )
        );

        if ( is_wp_error( $create_result ) ) {
            return new WP_REST_Response(
                array(
                    'message' => __( 'Could not create booking', 'professional-booking-system' ),
                    'error'   => $create_result->get_error_message(),
                ),
                500
            );
        }

        $booking_id = isset( $create_result['id'] ) ? (int) $create_result['id'] : 0;
        if ( ! $booking_id ) {
            return new WP_REST_Response(
                array( 'message' => __( 'Could not create booking', 'professional-booking-system' ) ),
                500
            );
        }

        $booking_obj = PBS_Bookings::get( $booking_id );

        return new WP_REST_Response(
            array(
                'message' => __( 'Booking created successfully', 'professional-booking-system' ),
                'booking' => array(
                    'id'                => (int) $booking_obj->id,
                    'service_id'        => (int) $booking_obj->service_id,
                    'date'              => $booking_obj->booking_date,
                    'time'              => $booking_obj->booking_time,
                    'status'            => $booking_obj->status,
                    'payment_status'    => $booking_obj->payment_status,
                    'cancellation_token'=> $booking_obj->cancellation_token,
                ),
            ),
            201
        );
    }

    public function mercadopago_create_preference( WP_REST_Request $request ) {
        // Esperamos booking_id en el body
        $params     = $request->get_json_params();
        $booking_id = isset( $params['booking_id'] ) ? intval( $params['booking_id'] ) : 0;

        if ( ! $booking_id ) {
            return new WP_REST_Response(
                array( 'message' => 'Missing booking_id' ),
                400
            );
        }

        $booking = PBS_Bookings::get_instance()->get_booking( $booking_id );
        if ( ! $booking ) {
            return new WP_REST_Response(
                array( 'message' => 'Booking not found' ),
                404
            );
        }

        $gateway = PBS_Payment_MercadoPago::get_instance();
        if ( ! $gateway->is_enabled() ) {
            return new WP_REST_Response(
                array( 'message' => 'MercadoPago disabled' ),
                400
            );
        }

        $result = $gateway->create_payment( $booking );

        if ( ! $result['success'] ) {
            return new WP_REST_Response(
                array(
                    'message' => 'Error creating MercadoPago preference',
                    'error'   => $result['error'],
                ),
                500
            );
        }

        return new WP_REST_Response(
            array(
                'redirect_url' => $result['redirect_url'],
            ),
            200
        );
    }

    public function mercadopago_webhook( WP_REST_Request $request ) {
        $gateway = PBS_Payment_MercadoPago::get_instance();
        return $gateway->handle_webhook( $request );
    }

    public function stripe_create_session( WP_REST_Request $request ) {
        $params     = $request->get_json_params();
        $booking_id = isset( $params['booking_id'] ) ? intval( $params['booking_id'] ) : 0;

        if ( ! $booking_id ) {
            return new WP_REST_Response(
                array( 'message' => 'Missing booking_id' ),
                400
            );
        }

        $booking = PBS_Bookings::get_instance()->get_booking( $booking_id );
        if ( ! $booking ) {
            return new WP_REST_Response(
                array( 'message' => 'Booking not found' ),
                404
            );
        }

        $gateway = PBS_Payment_Stripe::get_instance();
        if ( ! $gateway->is_enabled() ) {
            return new WP_REST_Response(
                array( 'message' => 'Stripe disabled' ),
                400
            );
        }

        $result = $gateway->create_payment( $booking );

        if ( ! $result['success'] ) {
            return new WP_REST_Response(
                array(
                    'message' => 'Error creating Stripe session',
                    'error'   => $result['error'],
                ),
                500
            );
        }

        // Devolvemos session_id y public_key para usar Stripe.js
        return new WP_REST_Response(
            array(
                'session_id' => $result['session_id'],
                'public_key' => $result['public_key'],
            ),
            200
        );
    }

    public function stripe_webhook( WP_REST_Request $request ) {
        $gateway = PBS_Payment_Stripe::get_instance();
        return $gateway->handle_webhook( $request );
    }
}