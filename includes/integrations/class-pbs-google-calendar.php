<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Integración con Google Calendar
 */
class PBS_Google_Calendar {

    /**
     * Singleton
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
     * ¿Está habilitada la integración?
     */
    public function is_enabled() {
        return get_option( 'pbs_gcal_enabled', '0' ) === '1'
            && get_option( 'pbs_gcal_client_id', '' ) !== ''
            && get_option( 'pbs_gcal_client_secret', '' ) !== ''
            && get_option( 'pbs_gcal_refresh_token', '' ) !== '';
    }

    /**
     * Obtener access token usando refresh token (OAuth2)
     */
    protected function get_access_token() {
        $client_id     = get_option( 'pbs_gcal_client_id', '' );
        $client_secret = get_option( 'pbs_gcal_client_secret', '' );
        $refresh_token = get_option( 'pbs_gcal_refresh_token', '' );

        error_log('[PBS GCal] Getting access token...');
        error_log('[PBS GCal] Client ID: ' . substr($client_id, 0, 20) . '...');
        error_log('[PBS GCal] Has Client Secret: ' . (!empty($client_secret) ? 'Yes' : 'No'));
        error_log('[PBS GCal] Has Refresh Token: ' . (!empty($refresh_token) ? 'Yes' : 'No'));

        if ( empty( $client_id ) || empty( $client_secret ) || empty( $refresh_token ) ) {
            error_log('[PBS GCal] Missing credentials');
            return new WP_Error( 'missing_credentials', 'Google Calendar credentials not configured' );
        }

        $response = wp_remote_post(
            'https://oauth2.googleapis.com/token',
            array(
                'body'    => array(
                    'client_id'     => $client_id,
                    'client_secret' => $client_secret,
                    'refresh_token' => $refresh_token,
                    'grant_type'    => 'refresh_token',
                ),
                'timeout' => 30,
            )
        );

        if ( is_wp_error( $response ) ) {
            error_log('[PBS GCal] WP_Error on token request: ' . $response->get_error_message());
            return $response;
        }

        $code = wp_remote_retrieve_response_code( $response );
        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        error_log('[PBS GCal] Token response code: ' . $code);
        error_log('[PBS GCal] Token response body: ' . $body);

        if ( $code >= 200 && $code < 300 && ! empty( $data['access_token'] ) ) {
            error_log('[PBS GCal] Access token obtained successfully');
            return $data['access_token'];
        }

        $error_msg = 'Error getting Google access token';
        if (!empty($data['error'])) {
            $error_msg .= ': ' . $data['error'];
            if (!empty($data['error_description'])) {
                $error_msg .= ' - ' . $data['error_description'];
            }
        }
        error_log('[PBS GCal] ' . $error_msg);

        return new WP_Error( 'gcal_auth_error', $error_msg );
    }

    /**
     * Crear evento en Google Calendar para una reserva
     *
     * @param array $booking Array con datos de la reserva (incl. service)
     * @return array ['success' => bool, 'event_id' => string, 'error' => string]
     */
    public function create_event_for_booking( $booking, $service ) {
        if ( ! $this->is_enabled() ) {
            return array(
                'success' => false,
                'error'   => 'Google Calendar is disabled',
            );
        }

        $access_token = $this->get_access_token();
        if ( is_wp_error( $access_token ) ) {
            return array(
                'success' => false,
                'error'   => $access_token->get_error_message(),
            );
        }

        $calendar_id = get_option( 'pbs_gcal_calendar_id', 'primary' );
        $timezone    = get_option( 'pbs_gcal_timezone', get_option( 'timezone_string', 'UTC' ) );

        // Construir fecha/hora inicio-fin
        $booking_date = isset( $booking['booking_date'] ) ? $booking['booking_date'] : '';
        $booking_time = isset( $booking['booking_time'] ) ? $booking['booking_time'] : '';
        $booking_time = strlen( $booking_time ) === 5 ? $booking_time . ':00' : $booking_time;

        if ( empty( $booking_date ) || empty( $booking_time ) ) {
            error_log( '[PBS GCal] Missing booking_date or booking_time for booking #' . ( isset( $booking['id'] ) ? $booking['id'] : 'unknown' ) );
            return array(
                'success' => false,
                'error'   => 'Missing booking date/time',
            );
        }

        $start_datetime = $booking_date . 'T' . $booking_time;
        $duration       = isset( $service['duration'] ) ? (int) $service['duration'] : 60;
        $start_ts       = strtotime( $start_datetime );
        if ( ! $start_ts ) {
            error_log( '[PBS GCal] Invalid start_datetime: ' . $start_datetime );
            return array(
                'success' => false,
                'error'   => 'Invalid booking date/time',
            );
        }
        $end_ts       = $start_ts + ( $duration * 60 );
        $end_datetime = date( 'Y-m-d\TH:i:s', $end_ts );

        $customer_name  = isset( $booking['customer_name'] ) ? $booking['customer_name'] : '';
        $customer_email = isset( $booking['customer_email'] ) ? $booking['customer_email'] : '';
        $customer_phone = isset( $booking['customer_phone'] ) ? $booking['customer_phone'] : '';
        $customer_notes = isset( $booking['customer_notes'] ) ? $booking['customer_notes'] : '';

        $service_name = isset( $service['name'] ) ? $service['name'] : '';
        $summary = trim( $service_name . ' - ' . $customer_name );
        if ( $summary === '-' || $summary === '' ) {
            $summary = 'Reserva';
        }
        $description_parts = array();

        $description_parts[] = sprintf( 'Cliente: %s (%s)', $customer_name, $customer_email );
        if ( ! empty( $customer_phone ) ) {
            $description_parts[] = 'Teléfono: ' . $customer_phone;
        }
        if ( ! empty( $customer_notes ) ) {
            $description_parts[] = 'Notas: ' . $customer_notes;
        }
        $description_parts[] = sprintf( 'ID de reserva: %d', $booking['id'] );

        $description = implode( "\n", $description_parts );

        // Evento base
        $event = array(
            'summary'     => $summary,
            'description' => $description,
            'start'       => array(
                'dateTime' => $start_datetime,
                'timeZone' => $timezone,
            ),
            'end'         => array(
                'dateTime' => $end_datetime,
                'timeZone' => $timezone,
            ),
        );

        if ( ! empty( $customer_email ) ) {
            $event['attendees'] = array(
                array( 'email' => $customer_email, 'displayName' => $customer_name ),
            );
        }

        // Solicitar Google Meet (conferenceData)
        if ( get_option( 'pbs_gcal_create_meet', '0' ) === '1' ) {
            $event['conferenceData'] = array(
                'createRequest' => array(
                    'requestId' => 'pbs-' . $booking['id'] . '-' . time(),
                    'conferenceSolutionKey' => array(
                        'type' => 'hangoutsMeet',
                    ),
                ),
            );
        }

        $response = wp_remote_post(
            add_query_arg(
                array( 'conferenceDataVersion' => 1 ),
                'https://www.googleapis.com/calendar/v3/calendars/' . rawurlencode( $calendar_id ) . '/events'
            ),
            array(
                'headers' => array(
                    'Authorization' => 'Bearer ' . $access_token,
                    'Content-Type'  => 'application/json',
                ),
                'body'    => wp_json_encode( $event ),
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
        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        if ( $code < 200 || $code >= 300 ) {
            error_log( '[PBS GCal] Create event failed. Code: ' . $code );
            error_log( '[PBS GCal] Create event request payload: ' . wp_json_encode( $event ) );
            error_log( '[PBS GCal] Create event response body: ' . $body );
        }

        if ( $code >= 200 && $code < 300 && ! empty( $data['id'] ) ) {
            $meet_link = '';
            if ( ! empty( $data['hangoutLink'] ) ) {
                $meet_link = $data['hangoutLink'];
            } elseif ( ! empty( $data['conferenceData']['entryPoints'] ) ) {
                foreach ( $data['conferenceData']['entryPoints'] as $entry ) {
                    if ( isset( $entry['entryPointType'], $entry['uri'] ) && $entry['entryPointType'] === 'video' ) {
                        $meet_link = $entry['uri'];
                        break;
                    }
                }
            }
            return array(
                'success'  => true,
                'event_id' => $data['id'],
                'meet_link' => $meet_link,
            );
        }

        $error_message = isset( $data['error']['message'] ) ? $data['error']['message'] : 'Unknown error';

        return array(
            'success' => false,
            'error'   => $error_message,
        );
    }

    /**
     * Obtener eventos ocupados en Google Calendar para un rango de tiempo
     *
     * @param string $start_datetime 'Y-m-d\TH:i:s' (en la zona horaria del calendario)
     * @param string $end_datetime   'Y-m-d\TH:i:s'
     * @return array|WP_Error Lista de eventos (cada uno con start/end) o WP_Error
     */
    public function get_busy_events( $start_datetime, $end_datetime ) {
        if ( ! $this->is_enabled() ) {
            return array(); // si no está habilitado, no bloqueamos nada
        }

        $access_token = $this->get_access_token();
        if ( is_wp_error( $access_token ) ) {
            return $access_token;
        }

        $calendar_id = get_option( 'pbs_gcal_calendar_id', 'primary' );
        $timezone    = get_option( 'pbs_gcal_timezone', get_option( 'timezone_string', 'UTC' ) );

        // timeMin/timeMax deben ir en RFC3339 con offset o 'Z'
        $time_min = $start_datetime . 'Z';
        $time_max = $end_datetime . 'Z';

        $url = add_query_arg(
            array(
                'timeMin' => $time_min,
                'timeMax' => $time_max,
                'singleEvents' => 'true',
                'orderBy' => 'startTime',
            ),
            'https://www.googleapis.com/calendar/v3/calendars/' . rawurlencode( $calendar_id ) . '/events'
        );

        $response = wp_remote_get(
            $url,
            array(
                'headers' => array(
                    'Authorization' => 'Bearer ' . $access_token,
                ),
                'timeout' => 30,
            )
        );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code( $response );
        $data = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( $code >= 200 && $code < 300 && ! empty( $data['items'] ) ) {
            $events = array();

            foreach ( $data['items'] as $item ) {
                // Ignoramos eventos cancelados
                if ( isset( $item['status'] ) && $item['status'] === 'cancelled' ) {
                    continue;
                }

                // Eventos all-day usan 'date', otros 'dateTime'
                if ( isset( $item['start']['dateTime'] ) && isset( $item['end']['dateTime'] ) ) {
                    $events[] = array(
                        'start' => $item['start']['dateTime'],
                        'end'   => $item['end']['dateTime'],
                    );
                } elseif ( isset( $item['start']['date'] ) && isset( $item['end']['date'] ) ) {
                    // All-day: lo tratamos como ocupado todo el día
                    $events[] = array(
                        'start' => $item['start']['date'] . 'T00:00:00',
                        'end'   => $item['end']['date'] . 'T23:59:59',
                    );
                }
            }

            return $events;
        }

        // Si falla, por seguridad devolvemos array vacío (no bloquear todos los slots)
        // pero podrías devolver WP_Error para debug
        return array();
    }
}