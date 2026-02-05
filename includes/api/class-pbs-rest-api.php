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

        // CSRF Token (público)
        register_rest_route(
            $namespace,
            '/csrf-token',
            array(
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array( $this, 'get_csrf_token' ),
                    'permission_callback' => '__return_true',
                ),
            )
        );

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
     * GET /csrf-token
     * Devuelve un token CSRF para usar en peticiones POST
     */
    public function get_csrf_token( WP_REST_Request $request ) {
        $token = wp_create_nonce( 'pbs_booking_csrf' );

        return new WP_REST_Response(
            array(
                'csrf_token' => $token,
            ),
            200
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
                'id'          => (int) $service['id'],
                'name'        => $service['name'],
                'description' => $service['description'],
                'duration'    => (int) $service['duration'],
                'price'       => (float) $service['price'],
                'currency'    => isset( $service['currency'] ) ? $service['currency'] : get_option( 'pbs_payment_currency', 'USD' ),
                'max_per_slot'=> isset( $service['max_per_slot'] ) ? (int) $service['max_per_slot'] : 1,
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
            'id'          => (int) $service['id'],
            'name'        => $service['name'],
            'description' => $service['description'],
            'duration'    => (int) $service['duration'],
            'price'       => (float) $service['price'],
            'currency'    => isset( $service['currency'] ) ? $service['currency'] : get_option( 'pbs_payment_currency', 'USD' ),
            'max_per_slot'=> isset( $service['max_per_slot'] ) ? (int) $service['max_per_slot'] : 1,
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
        // day_of_week: 0=Domingo, 1=Lunes, ..., 6=Sábado
        // date('w') retorna el día de la semana: 0=Sunday, 1=Monday, ..., 6=Saturday
        $day_of_week = (int) date( 'w', strtotime( $date ) );
        $schedules = PBS_Schedules::get_schedules_by_day( $day_of_week );

        // Si no hay horarios para ese día
        if ( empty( $schedules ) ) {
            return new WP_REST_Response(
                array(
                    'date'  => $date,
                    'slots' => array(),
                ),
                200
            );
        }

        // Aplicar excepciones (día bloqueado completo)
        $is_blocked = PBS_Schedules::is_day_blocked( $date );
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
                $is_taken = PBS_Bookings::is_slot_taken(
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
                    $duration = isset( $service['duration'] ) ? (int) $service['duration'] : 60;

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

        // Rate limiting: máximo 10 reservas por minuto por IP
        $security = PBS_Security::get_instance();
        if (!$security->check_rate_limit('bookings_create', 10, 60)) {
            return new WP_REST_Response(
                array('message' => __('Demasiadas solicitudes. Por favor, intenta más tarde.', 'professional-booking-system')),
                429
            );
        }

        $params = $request->get_json_params();
        if ( empty( $params ) ) {
            $params = $request->get_params(); // por si viene como form-data
        }

        // Validación de seguridad: aceptar CSRF token personalizado O nonce de WordPress
        $csrf_token = $request->get_header( 'X-CSRF-Token' );
        if ( empty( $csrf_token ) && ! empty( $params['csrf_token'] ) ) {
            $csrf_token = sanitize_text_field( $params['csrf_token'] );
        }

        // Validar token CSRF propio (PBS_Security) o nonce CSRF estático
        $csrf_valid = false;
        if ( ! empty( $csrf_token ) ) {
            if ( $security->verify_csrf_token( $csrf_token ) ) {
                $csrf_valid = true;
            } elseif ( wp_verify_nonce( $csrf_token, 'pbs_booking_csrf' ) ) {
                $csrf_valid = true;
            }
        }

        // Si no hay CSRF válido, intentar validar el nonce de WordPress
        if ( ! $csrf_valid ) {
            // Alternativa: aceptar el nonce de WordPress
            $nonce = $request->get_header( 'X-WP-Nonce' );
            if ( empty( $nonce ) && ! empty( $params['nonce'] ) ) {
                $nonce = sanitize_text_field( $params['nonce'] );
            }

            if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
                return new WP_REST_Response(
                    array( 'message' => __( 'Invalid CSRF token or nonce', 'professional-booking-system' ) ),
                    403
                );
            }
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
        $service = PBS_Services::get_service( $service_id );
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

        // Obtener el servicio para calcular payment_amount y duration
        $service = PBS_Services::get_service( $service_id );
        if ( ! $service ) {
            return new WP_REST_Response(
                array( 'message' => __( 'Service not found', 'professional-booking-system' ) ),
                404
            );
        }

        // En PBS_REST_API::create_booking() justo antes de crear la reserva
        if ( class_exists( 'PBS_Google_Calendar' ) ) {
            try {
                error_log( 'PBS: Checking if Google Calendar is enabled' );
                if ( PBS_Google_Calendar::get_instance()->is_enabled() ) {
                    error_log( 'PBS: Google Calendar is ENABLED, checking busy events' );
                    $gcal = PBS_Google_Calendar::get_instance();

                    $duration = isset( $service['duration'] ) ? (int) $service['duration'] : 60;

                    $start_datetime = $date . 'T' . $time . ':00';
                    $start_ts = strtotime( $start_datetime );
                    $end_ts   = $start_ts + ( $duration * 60 );
                    $end_datetime = date( 'Y-m-d\TH:i:s', $end_ts );

                    error_log( 'PBS: Checking Google Calendar busy events from ' . $start_datetime . ' to ' . $end_datetime );

                    $busy_events = $gcal->get_busy_events( $start_datetime, $end_datetime );

                    error_log( 'PBS: Google Calendar busy events result: ' . wp_json_encode( $busy_events ) );

                    if ( ! is_wp_error( $busy_events ) && ! empty( $busy_events ) ) {
                        error_log( 'PBS: Found busy events on Google Calendar, blocking slot' );
                        // Si hay algún evento que solape, devolvemos error
                        return new WP_REST_Response(
                            array(
                                'message' => 'Selected time is no longer available',
                                'code'    => 'slot_taken_google',
                            ),
                            409
                        );
                    } else {
                        error_log( 'PBS: No busy events found on Google Calendar' );
                    }
                } else {
                    error_log( 'PBS: Google Calendar is NOT enabled' );
                }
            } catch ( Exception $e ) {
                // Log error but continue with booking - don't block booking if Google Calendar fails
                error_log( 'PBS Google Calendar check failed: ' . $e->getMessage() );
            }
        }

        // Crear reserva (usando método ya existente)
        try {
            error_log( 'PBS: Starting booking creation for service_id=' . $service_id );
            
            $booking_data = array(
                'service_id' => $service_id,
                'customer_name' => $name,
                'customer_email' => $email,
                'customer_phone' => $phone,
                'booking_date' => $date,
                'booking_time' => $time,
                'customer_notes' => $notes,
                'duration' => isset( $service['duration'] ) ? (int) $service['duration'] : 60,
                'payment_amount' => isset( $service['price'] ) ? (float) $service['price'] : 0.00,
            );
            
            error_log( 'PBS: Booking data: ' . wp_json_encode( $booking_data ) );
            
            $create_result = PBS_Bookings::create_booking( $booking_data );
            
            error_log( 'PBS: create_booking result: ' . wp_json_encode( $create_result ) );

            if ( is_wp_error( $create_result ) ) {
                error_log( 'PBS: WP_Error in create_booking: ' . $create_result->get_error_message() );
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
                error_log( 'PBS: No booking_id in result' );
                return new WP_REST_Response(
                    array( 'message' => __( 'Could not create booking', 'professional-booking-system' ) ),
                    500
                );
            }

            error_log( 'PBS: Booking created with ID: ' . $booking_id );

            $booking_obj = PBS_Bookings::get( $booking_id );

            if ( ! $booking_obj ) {
                error_log( 'PBS: Could not load booking with ID: ' . $booking_id );
                return new WP_REST_Response(
                    array( 'message' => __( 'Could not load booking', 'professional-booking-system' ) ),
                    500
                );
            }
            
            error_log( 'PBS: Booking loaded successfully' );
            
        } catch ( Exception $e ) {
            error_log( 'PBS: Fatal error in create_booking: ' . $e->getMessage() . ' | Trace: ' . $e->getTraceAsString() );
            return new WP_REST_Response(
                array(
                    'message' => __( 'Could not create booking', 'professional-booking-system' ),
                    'error'   => $e->getMessage(),
                ),
                500
            );
        }

        try {
            $security->log_audit(
                'booking_created',
                'booking',
                $booking_id,
                null,
                array(
                    'service_id' => $service_id,
                    'date'       => $date,
                    'time'       => $time,
                    'email'      => $email,
                )
            );
        } catch ( Exception $e ) {
            error_log( 'PBS: Error logging audit: ' . $e->getMessage() );
        }

        error_log( 'PBS: Booking response data: ' . wp_json_encode( $booking_obj ) );

        $response_data = array(
            'message' => __( 'Booking created successfully', 'professional-booking-system' ),
            'booking' => array(
                'id'                => isset( $booking_obj['id'] ) ? (int) $booking_obj['id'] : $booking_id,
                'service_id'        => isset( $booking_obj['service_id'] ) ? (int) $booking_obj['service_id'] : $service_id,
                'date'              => isset( $booking_obj['booking_date'] ) ? $booking_obj['booking_date'] : $date,
                'time'              => isset( $booking_obj['booking_time'] ) ? $booking_obj['booking_time'] : $time,
                'status'            => isset( $booking_obj['status'] ) ? $booking_obj['status'] : 'pending',
                'payment_status'    => isset( $booking_obj['payment_status'] ) ? $booking_obj['payment_status'] : 'pending',
                'cancellation_token'=> isset( $booking_obj['cancellation_token'] ) ? $booking_obj['cancellation_token'] : '',
            ),
        );

        error_log( 'PBS: Returning response: ' . wp_json_encode( $response_data ) );

        return new WP_REST_Response( $response_data, 201 );
    }

    public function mercadopago_create_preference( WP_REST_Request $request ) {
        $security = PBS_Security::get_instance();
        if ( ! $security->check_rate_limit( 'mercadopago_create_preference', 20, 60 ) ) {
            return new WP_REST_Response(
                array( 'message' => __( 'Demasiadas solicitudes. Por favor, intenta más tarde.', 'professional-booking-system' ) ),
                429
            );
        }
        // Esperamos booking_id en el body
        $params     = $request->get_json_params();
        $booking_id = isset( $params['booking_id'] ) ? intval( $params['booking_id'] ) : 0;

        if ( ! $booking_id ) {
            return new WP_REST_Response(
                array( 'message' => 'Missing booking_id' ),
                400
            );
        }

        $booking = PBS_Bookings::get_booking( $booking_id );
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
        $security = PBS_Security::get_instance();
        if ( ! $security->check_rate_limit( 'stripe_create_session', 20, 60 ) ) {
            return new WP_REST_Response(
                array( 'message' => __( 'Demasiadas solicitudes. Por favor, intenta más tarde.', 'professional-booking-system' ) ),
                429
            );
        }
        $params     = $request->get_json_params();
        $booking_id = isset( $params['booking_id'] ) ? intval( $params['booking_id'] ) : 0;

        if ( ! $booking_id ) {
            return new WP_REST_Response(
                array( 'message' => 'Missing booking_id' ),
                400
            );
        }

        $booking = PBS_Bookings::get_booking( $booking_id );
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