<?php
/**
 * Panel de administración
 *
 * @package Professional_Booking_System
 */

if (!defined('ABSPATH')) {
    exit;
}

class PBS_Admin {

    /**
     * Instancia única (Singleton)
     */
    private static $instance = null;

    /**
     * Obtener instancia
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
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        
        // AJAX handlers
        add_action('wp_ajax_pbs_save_service', array($this, 'ajax_save_service'));
        add_action('wp_ajax_pbs_delete_service', array($this, 'ajax_delete_service'));
        add_action('wp_ajax_pbs_save_schedule', array($this, 'ajax_save_schedule'));
        add_action('wp_ajax_pbs_delete_schedule', array($this, 'ajax_delete_schedule'));
        add_action('wp_ajax_pbs_save_exception', array($this, 'ajax_save_exception'));
        add_action('wp_ajax_pbs_update_booking_status', array($this, 'ajax_update_booking_status'));
        add_action('wp_ajax_pbs_disconnect_google', array($this, 'ajax_disconnect_google'));
        add_action('wp_ajax_pbs_generate_meet', array($this, 'ajax_generate_meet'));
    }

    /**
     * Agregar menú de administración
     */
    public function add_admin_menu() {
        // Menú principal
        add_menu_page(
            __('Professional Booking', 'professional-booking-system'),
            __('Reservas', 'professional-booking-system'),
            'manage_options',
            'professional-booking',
            array($this, 'render_dashboard_page'),
            'dashicons-calendar-alt',
            30
        );

        // Submenú: Dashboard
        add_submenu_page(
            'professional-booking',
            __('Dashboard', 'professional-booking-system'),
            __('Dashboard', 'professional-booking-system'),
            'manage_options',
            'professional-booking',
            array($this, 'render_dashboard_page')
        );

        // Submenú: Reservas
        add_submenu_page(
            'professional-booking',
            __('Reservas', 'professional-booking-system'),
            __('Todas las Reservas', 'professional-booking-system'),
            'manage_options',
            'professional-booking-bookings',
            array($this, 'render_bookings_page')
        );

        // Submenú: Servicios
        add_submenu_page(
            'professional-booking',
            __('Servicios', 'professional-booking-system'),
            __('Servicios', 'professional-booking-system'),
            'manage_options',
            'professional-booking-services',
            array($this, 'render_services_page')
        );

        // Submenú: Horarios
        add_submenu_page(
            'professional-booking',
            __('Horarios', 'professional-booking-system'),
            __('Horarios', 'professional-booking-system'),
            'manage_options',
            'professional-booking-schedules',
            array($this, 'render_schedules_page')
        );

        // Submenú: Excepciones
        add_submenu_page(
            'professional-booking',
            __('Excepciones', 'professional-booking-system'),
            __('Días Bloqueados', 'professional-booking-system'),
            'manage_options',
            'professional-booking-exceptions',
            array($this, 'render_exceptions_page')
        );

        // Submenú: Configuración
        add_submenu_page(
            'professional-booking',
            __('Configuración', 'professional-booking-system'),
            __('Configuración', 'professional-booking-system'),
            'manage_options',
            'professional-booking-settings',
            array($this, 'render_settings_page')
        );
    }

    /**
     * Registrar configuraciones
     */
    public function register_settings() {
        // Configuración general
        register_setting('pbs_general_settings', 'pbs_professional_name');
        register_setting('pbs_general_settings', 'pbs_professional_specialty');
        register_setting('pbs_general_settings', 'pbs_default_duration');
        register_setting('pbs_general_settings', 'pbs_buffer_time');
        register_setting('pbs_general_settings', 'pbs_min_booking_notice');
        register_setting('pbs_general_settings', 'pbs_max_booking_notice');
        register_setting('pbs_general_settings', 'pbs_timezone');

        // Configuración de pagos
        register_setting('pbs_payment_settings', 'pbs_currency');
        register_setting('pbs_payment_settings', 'pbs_stripe_test_mode');
        register_setting('pbs_payment_settings', 'pbs_stripe_test_publishable_key');
        register_setting('pbs_payment_settings', 'pbs_stripe_test_secret_key');
        register_setting('pbs_payment_settings', 'pbs_stripe_live_publishable_key');
        register_setting('pbs_payment_settings', 'pbs_stripe_live_secret_key');
        register_setting('pbs_payment_settings', 'pbs_require_payment');
        register_setting('pbs_payment_settings', 'pbs_payment_percentage');
        register_setting('pbs_payment_settings', 'pbs_cancellation_hours');

        register_setting( 'pbs_settings_group', 'pbs_payment_provider' );
        register_setting( 'pbs_settings_group', 'pbs_mercadopago_public_key' );
        register_setting( 'pbs_settings_group', 'pbs_mercadopago_access_token' );
        register_setting( 'pbs_settings_group', 'pbs_payment_currency' ); // ARS, USD, etc.

        register_setting( 'pbs_settings_group', 'pbs_stripe_public_key' );
        register_setting( 'pbs_settings_group', 'pbs_stripe_secret_key' );
        register_setting( 'pbs_settings_group', 'pbs_stripe_mode' ); // live | test

        register_setting( 'pbs_settings_group', 'pbs_paypal_client_id' );
        register_setting( 'pbs_settings_group', 'pbs_paypal_secret' );
        register_setting( 'pbs_settings_group', 'pbs_paypal_mode' ); // sandbox | live

        // Configuración de videollamadas
        register_setting('pbs_videocall_settings', 'pbs_enable_videocalls');
        register_setting('pbs_videocall_settings', 'pbs_google_client_id');
        register_setting('pbs_videocall_settings', 'pbs_google_client_secret');

        // Configuración de notificaciones
        register_setting('pbs_notification_settings', 'pbs_reminder_24h');
        register_setting('pbs_notification_settings', 'pbs_reminder_2h');
        register_setting('pbs_notification_settings', 'pbs_email_from_name');
        register_setting('pbs_notification_settings', 'pbs_email_from_address');
        register_setting( 'pbs_settings_group', 'pbs_email_admin_address' );
        register_setting( 'pbs_settings_group', 'pbs_email_client_subject' );
        register_setting( 'pbs_settings_group', 'pbs_email_admin_subject' );
        register_setting( 'pbs_settings_group', 'pbs_email_client_template' );
        register_setting( 'pbs_settings_group', 'pbs_email_admin_template' );
        register_setting( 'pbs_settings_group', 'pbs_email_send_admin' );
        register_setting( 'pbs_settings_group', 'pbs_email_send_client' );

        register_setting( 'pbs_settings_group', 'pbs_gcal_enabled', array(
            'sanitize_callback' => function($value) { return $value ? 1 : 0; }
        ));
        register_setting( 'pbs_settings_group', 'pbs_gcal_calendar_id', array(
            'sanitize_callback' => 'sanitize_text_field'
        ));
        register_setting( 'pbs_settings_group', 'pbs_gcal_client_id', array(
            'sanitize_callback' => 'sanitize_text_field'
        ));
        register_setting( 'pbs_settings_group', 'pbs_gcal_client_secret', array(
            'sanitize_callback' => 'sanitize_text_field'
        ));
        register_setting( 'pbs_settings_group', 'pbs_gcal_refresh_token', array(
            'sanitize_callback' => 'sanitize_text_field'
        ));
        register_setting( 'pbs_settings_group', 'pbs_gcal_timezone', array(
            'sanitize_callback' => 'sanitize_text_field'
        ));
        register_setting( 'pbs_settings_group', 'pbs_gcal_create_meet', array(
            'sanitize_callback' => function($value) { return $value ? 1 : 0; }
        ));
        register_setting( 'pbs_settings_group', 'pbs_gcal_authorized_email', array(
            'sanitize_callback' => 'sanitize_email'
        ));
        register_setting( 'pbs_settings_group', 'pbs_gcal_meet_enabled', array(
            'sanitize_callback' => function($value) { return $value ? 1 : 0; }
        ));


    }

    /**
     * Renderizar página de Dashboard
     */
    public function render_dashboard_page() {
        global $wpdb;

        // Estadísticas rápidas
        $table_bookings = $wpdb->prefix . 'pbs_bookings';
        
        $total_bookings = $wpdb->get_var("SELECT COUNT(*) FROM $table_bookings");
        $pending_bookings = $wpdb->get_var("SELECT COUNT(*) FROM $table_bookings WHERE status = 'pending'");
        $confirmed_bookings = $wpdb->get_var("SELECT COUNT(*) FROM $table_bookings WHERE status = 'confirmed'");
        $today_bookings = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_bookings WHERE booking_date = %s AND status IN ('pending', 'confirmed')",
            current_time('Y-m-d')
        ));

        ?>
        <div class="wrap">
            <h1><?php _e('Dashboard - Professional Booking System', 'professional-booking-system'); ?></h1>

            <div class="pbs-dashboard-stats">
                <div class="pbs-stat-box">
                    <h3><?php echo esc_html($total_bookings); ?></h3>
                    <p><?php _e('Total Reservas', 'professional-booking-system'); ?></p>
                </div>
                <div class="pbs-stat-box">
                    <h3><?php echo esc_html($pending_bookings); ?></h3>
                    <p><?php _e('Pendientes', 'professional-booking-system'); ?></p>
                </div>
                <div class="pbs-stat-box">
                    <h3><?php echo esc_html($confirmed_bookings); ?></h3>
                    <p><?php _e('Confirmadas', 'professional-booking-system'); ?></p>
                </div>
                <div class="pbs-stat-box">
                    <h3><?php echo esc_html($today_bookings); ?></h3>
                    <p><?php _e('Hoy', 'professional-booking-system'); ?></p>
                </div>
            </div>

            <h2><?php _e('Próximas Reservas', 'professional-booking-system'); ?></h2>
            <?php
            $upcoming = $wpdb->get_results($wpdb->prepare(
                "SELECT b.*, s.name as service_name 
                 FROM $table_bookings b
                 LEFT JOIN {$wpdb->prefix}pbs_services s ON b.service_id = s.id
                 WHERE b.booking_date >= %s 
                 AND b.status IN ('pending', 'confirmed')
                 ORDER BY b.booking_date ASC, b.booking_time ASC
                 LIMIT 10",
                current_time('Y-m-d')
            ), ARRAY_A);

            if ($upcoming) {
                echo '<table class="wp-list-table widefat fixed striped">';
                echo '<thead><tr>';
                echo '<th>' . __('Cliente', 'professional-booking-system') . '</th>';
                echo '<th>' . __('Servicio', 'professional-booking-system') . '</th>';
                echo '<th>' . __('Fecha', 'professional-booking-system') . '</th>';
                echo '<th>' . __('Hora', 'professional-booking-system') . '</th>';
                echo '<th>' . __('Estado', 'professional-booking-system') . '</th>';
                echo '<th>' . __('Google Meet', 'professional-booking-system') . '</th>';
                echo '</tr></thead><tbody>';

                foreach ($upcoming as $booking) {
                    echo '<tr>';
                    echo '<td>' . esc_html($booking['customer_name']) . '</td>';
                    echo '<td>' . esc_html($booking['service_name']) . '</td>';
                    echo '<td>' . esc_html(date_i18n(get_option('date_format'), strtotime($booking['booking_date']))) . '</td>';
                    echo '<td>' . esc_html(date_i18n(get_option('time_format'), strtotime($booking['booking_time']))) . '</td>';
                    echo '<td><span class="pbs-status pbs-status-' . esc_attr($booking['status']) . '">' . esc_html(ucfirst($booking['status'])) . '</span></td>';
                    if ( ! empty( $booking['videocall_link'] ) ) {
                        echo '<td><a href="' . esc_url( $booking['videocall_link'] ) . '" target="_blank">' . __( 'Join', 'professional-booking-system' ) . '</a></td>';
                    }
                    echo '</tr>';
                }

                echo '</tbody></table>';
            } else {
                echo '<p>' . __('No hay reservas próximas.', 'professional-booking-system') . '</p>';
            }
            ?>
        </div>
        <?php
    }

    /**
     * Renderizar página de Reservas
     */
    public function render_bookings_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        // Parámetros de filtro
        $status       = isset( $_GET['status'] ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : '';
        $payment      = isset( $_GET['payment_status'] ) ? sanitize_text_field( wp_unslash( $_GET['payment_status'] ) ) : '';
        $service_id   = isset( $_GET['service_id'] ) ? intval( $_GET['service_id'] ) : 0;
        $search       = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
        $paged        = isset( $_GET['paged'] ) ? max( 1, intval( $_GET['paged'] ) ) : 1;
        $per_page     = 20;

        $bookings_obj = PBS_Bookings::get_instance();

        // Obtener reservas filtradas (implementaremos el método más abajo)
        list( $bookings, $total ) = PBS_Bookings::get_bookings_admin_list(
            array(
                'status'         => $status,
                'payment_status' => $payment,
                'service_id'     => $service_id,
                'search'         => $search,
                'paged'          => $paged,
                'per_page'       => $per_page,
            )
        );

        $total_pages = ceil( $total / $per_page );
        $services    = PBS_Services::get_all( array( 'status' => 'active' ) );
        ?>
        <div class="wrap pbs-admin-wrap">
            <h1><?php _e( 'Bookings', 'professional-booking-system' ); ?></h1>

            <form method="get" class="pbs-bookings-filters">
                <input type="hidden" name="page" value="pbs-bookings">

                <div class="pbs-filters-row">
                    <div>
                        <label><?php _e( 'Status', 'professional-booking-system' ); ?></label>
                        <select name="status">
                            <option value=""><?php _e( 'All', 'professional-booking-system' ); ?></option>
                            <option value="pending" <?php selected( $status, 'pending' ); ?>><?php _e( 'Pending', 'professional-booking-system' ); ?></option>
                            <option value="confirmed" <?php selected( $status, 'confirmed' ); ?>><?php _e( 'Confirmed', 'professional-booking-system' ); ?></option>
                            <option value="cancelled" <?php selected( $status, 'cancelled' ); ?>><?php _e( 'Cancelled', 'professional-booking-system' ); ?></option>
                        </select>
                    </div>

                    <div>
                        <label><?php _e( 'Payment', 'professional-booking-system' ); ?></label>
                        <select name="payment_status">
                            <option value=""><?php _e( 'All', 'professional-booking-system' ); ?></option>
                            <option value="unpaid" <?php selected( $payment, 'unpaid' ); ?>><?php _e( 'Unpaid', 'professional-booking-system' ); ?></option>
                            <option value="paid" <?php selected( $payment, 'paid' ); ?>><?php _e( 'Paid', 'professional-booking-system' ); ?></option>
                            <option value="refunded" <?php selected( $payment, 'refunded' ); ?>><?php _e( 'Refunded', 'professional-booking-system' ); ?></option>
                        </select>
                    </div>

                    <div>
                        <label><?php _e( 'Service', 'professional-booking-system' ); ?></label>
                        <select name="service_id">
                            <option value="0"><?php _e( 'All', 'professional-booking-system' ); ?></option>
                            <?php foreach ( $services as $service ) : ?>
                                <option value="<?php echo esc_attr( $service['id'] ); ?>" <?php selected( $service_id, $service['id'] ); ?>>
                                    <?php echo esc_html( $service['name'] ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label><?php _e( 'Search', 'professional-booking-system' ); ?></label>
                        <input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'Name, email, ID…', 'professional-booking-system' ); ?>">
                    </div>

                    <div class="pbs-filters-actions">
                        <button class="button button-primary"><?php _e( 'Filter', 'professional-booking-system' ); ?></button>
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=pbs-bookings' ) ); ?>" class="button"><?php _e( 'Clear', 'professional-booking-system' ); ?></a>
                    </div>
                </div>
            </form>

            <table class="widefat fixed striped pbs-bookings-table">
                <thead>
                    <tr>
                        <th><?php _e( 'ID', 'professional-booking-system' ); ?></th>
                        <th><?php _e( 'Date/Time', 'professional-booking-system' ); ?></th>
                        <th><?php _e( 'Service', 'professional-booking-system' ); ?></th>
                        <th><?php _e( 'Client', 'professional-booking-system' ); ?></th>
                        <th><?php _e( 'Status', 'professional-booking-system' ); ?></th>
                        <th><?php _e( 'Payment', 'professional-booking-system' ); ?></th>
                        <th><?php _e( 'Video', 'professional-booking-system' ); ?></th>
                        <th><?php _e( 'Actions', 'professional-booking-system' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( ! empty( $bookings ) ) : ?>
                        <?php foreach ( $bookings as $booking ) : ?>
                            <tr data-booking-id="<?php echo esc_attr( $booking['id'] ); ?>">
                                <td>#<?php echo esc_html( $booking['id'] ); ?></td>
                                <td>
                                    <?php
                                    echo esc_html( $booking['booking_date'] . ' ' . substr( $booking['booking_time'], 0, 5 ) );
                                    ?>
                                </td>
                                <td><?php echo esc_html( $booking['service_name'] ); ?></td>
                                <td>
                                    <?php echo esc_html( $booking['customer_name'] ); ?><br>
                                    <small><?php echo esc_html( $booking['customer_email'] ); ?></small>
                                </td>
                                <td>
                                    <span class="pbs-status-badge pbs-status-<?php echo esc_attr( $booking['status'] ); ?>">
                                        <?php echo esc_html( ucfirst( $booking['status'] ) ); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="pbs-status-badge pbs-payment-<?php echo esc_attr( $booking['payment_status'] ); ?>">
                                        <?php echo esc_html( ucfirst( $booking['payment_status'] ) ); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ( ! empty( $booking['videocall_link'] ) ) : ?>
                                        <a href="<?php echo esc_url( $booking['videocall_link'] ); ?>" target="_blank" class="button button-small">
                                            <?php _e( 'Join', 'professional-booking-system' ); ?>
                                        </a>
                                    <?php else : ?>
                                        <span class="dashicons dashicons-minus"></span>
                                    <?php endif; ?>
                                </td>
                                <td class="pbs-booking-actions">
                                    <a href="#" class="button button-small pbs-view-booking" data-booking-id="<?php echo esc_attr( $booking['id'] ); ?>">
                                        <?php _e( 'View', 'professional-booking-system' ); ?>
                                    </a>
                                    <?php if ( $booking['status'] !== 'confirmed' ) : ?>
                                        <a href="#" class="button button-small pbs-confirm-booking" data-booking-id="<?php echo esc_attr( $booking['id'] ); ?>">
                                            <?php _e( 'Confirm', 'professional-booking-system' ); ?>
                                        </a>
                                    <?php endif; ?>
                                    <?php if ( $booking['status'] === 'confirmed' ) : ?>
                                        <a href="#" class="button button-small pbs-generate-meet" data-booking-id="<?php echo esc_attr( $booking['id'] ); ?>" title="<?php _e( 'Generate or regenerate Google Meet link', 'professional-booking-system' ); ?>">
                                            <?php _e( 'Meet', 'professional-booking-system' ); ?>
                                        </a>
                                    <?php endif; ?>
                                    <?php if ( $booking['status'] !== 'cancelled' ) : ?>
                                        <a href="#" class="button button-small pbs-cancel-booking" data-booking-id="<?php echo esc_attr( $booking['id'] ); ?>">
                                            <?php _e( 'Cancel', 'professional-booking-system' ); ?>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="8"><?php _e( 'No bookings found.', 'professional-booking-system' ); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <?php if ( $total_pages > 1 ) : ?>
                <div class="tablenav">
                    <div class="tablenav-pages">
                        <?php
                        echo paginate_links(
                            array(
                                'base'      => add_query_arg( 'paged', '%#%' ),
                                'format'    => '',
                                'current'   => $paged,
                                'total'     => $total_pages,
                                'prev_text' => '&laquo;',
                                'next_text' => '&raquo;',
                            )
                        );
                        ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <?php
        // Modal para detalle de reserva
        $this->render_booking_detail_modal();
    }

    public function render_booking_detail_modal() {
        ?>
        <div id="pbs-booking-detail-modal" class="pbs-modal" style="display:none;">
            <div class="pbs-modal-dialog">
                <div class="pbs-modal-header">
                    <h2><?php _e( 'Booking detail', 'professional-booking-system' ); ?></h2>
                    <span class="pbs-modal-close">&times;</span>
                </div>
                <div class="pbs-modal-body">
                    <div class="pbs-booking-detail-content">
                        <!-- se rellena por AJAX -->
                    </div>
                </div>
                <div class="pbs-modal-footer">
                    <button type="button" class="button pbs-modal-close-btn"><?php _e( 'Close', 'professional-booking-system' ); ?></button>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Renderizar página de Servicios
     */
    public function render_services_page() {
        $services = PBS_Services::get_all(array('status' => 'all'));

        ?>
        <div class="wrap">
            <h1><?php _e('Gestión de Servicios', 'professional-booking-system'); ?>
                <button class="page-title-action" id="pbs-add-service-btn"><?php _e('Agregar Nuevo', 'professional-booking-system'); ?></button>
            </h1>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Nombre', 'professional-booking-system'); ?></th>
                        <th><?php _e('Duración (min)', 'professional-booking-system'); ?></th>
                        <th><?php _e('Precio', 'professional-booking-system'); ?></th>
                        <th><?php _e('Videollamada', 'professional-booking-system'); ?></th>
                        <th><?php _e('Estado', 'professional-booking-system'); ?></th>
                        <th><?php _e('Acciones', 'professional-booking-system'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($services): ?>
                        <?php foreach ($services as $service): ?>
                            <tr>
                                <td><strong><?php echo esc_html($service->name); ?></strong><br>
                                    <small><?php echo esc_html($service->description); ?></small>
                                </td>
                                <td><?php echo esc_html($service->duration); ?></td>
                                <td><?php echo esc_html(get_option('pbs_currency', 'USD')) . ' ' . number_format($service->price, 2); ?></td>
                                <td><?php echo $service->enable_videocall ? __('Sí', 'professional-booking-system') : __('No', 'professional-booking-system'); ?></td>
                                <td><span class="pbs-status pbs-status-<?php echo esc_attr($service->status); ?>"><?php echo esc_html(ucfirst($service->status)); ?></span></td>
                                <td>
                                    <button class="button pbs-edit-service" data-id="<?php echo esc_attr($service->id); ?>"><?php _e('Editar', 'professional-booking-system'); ?></button>
                                    <button class="button pbs-delete-service" data-id="<?php echo esc_attr($service->id); ?>"><?php _e('Eliminar', 'professional-booking-system'); ?></button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6"><?php _e('No hay servicios creados.', 'professional-booking-system'); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Modal para agregar/editar servicio -->
            <div id="pbs-service-modal" class="pbs-modal" style="display:none;">
                <div class="pbs-modal-content">
                    <span class="pbs-modal-close">&times;</span>
                    <h2 id="pbs-service-modal-title"><?php _e('Agregar Servicio', 'professional-booking-system'); ?></h2>
                    <form id="pbs-service-form">
                        <input type="hidden" id="pbs-service-id" name="service_id" value="">
                        
                        <p>
                            <label><?php _e('Nombre del servicio', 'professional-booking-system'); ?> *</label>
                            <input type="text" name="name" id="pbs-service-name" required class="widefat">
                        </p>

                        <p>
                            <label><?php _e('Descripción', 'professional-booking-system'); ?></label>
                            <textarea name="description" id="pbs-service-description" rows="3" class="widefat"></textarea>
                        </p>

                        <p>
                            <label><?php _e('Duración (minutos)', 'professional-booking-system'); ?> *</label>
                            <select name="duration" id="pbs-service-duration" required>
                                <option value="15">15</option>
                                <option value="30">30</option>
                                <option value="45">45</option>
                                <option value="60" selected>60</option>
                                <option value="90">90</option>
                                <option value="120">120</option>
                            </select>
                        </p>

                        <p>
                            <label><?php _e('Precio', 'professional-booking-system'); ?> *</label>
                            <input type="number" name="price" id="pbs-service-price" step="0.01" min="0" required>
                        </p>

                        <p>
                            <label>
                                <input type="checkbox" name="enable_videocall" id="pbs-service-videocall" value="1">
                                <?php _e('Habilitar videollamada', 'professional-booking-system'); ?>
                            </label>
                        </p>

                        <p>
                            <label><?php _e('Categoría', 'professional-booking-system'); ?></label>
                            <input type="text" name="category" id="pbs-service-category" class="widefat">
                        </p>

                        <p>
                            <button type="submit" class="button button-primary"><?php _e('Guardar', 'professional-booking-system'); ?></button>
                            <button type="button" class="button pbs-modal-close"><?php _e('Cancelar', 'professional-booking-system'); ?></button>
                        </p>
                    </form>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Renderizar página de Horarios
     */
    public function render_schedules_page() {
        global $wpdb;

        $days = array(
            0 => __('Domingo', 'professional-booking-system'),
            1 => __('Lunes', 'professional-booking-system'),
            2 => __('Martes', 'professional-booking-system'),
            3 => __('Miércoles', 'professional-booking-system'),
            4 => __('Jueves', 'professional-booking-system'),
            5 => __('Viernes', 'professional-booking-system'),
            6 => __('Sábado', 'professional-booking-system'),
        );

        ?>
        <div class="wrap">
            <h1><?php _e('Gestión de Horarios', 'professional-booking-system'); ?>
                <button class="page-title-action" id="pbs-add-schedule-btn"><?php _e('Agregar Horario', 'professional-booking-system'); ?></button>
            </h1>

            <?php foreach ($days as $day_num => $day_name): ?>
                <?php
                $schedules = PBS_Schedules::get_schedules_by_day($day_num, false);
                ?>
                <h3><?php echo esc_html($day_name); ?></h3>
                <?php if ($schedules): ?>
                    <table class="wp-list-table widefat fixed striped" style="max-width: 600px;">
                        <thead>
                            <tr>
                                <th><?php _e('Hora Inicio', 'professional-booking-system'); ?></th>
                                <th><?php _e('Hora Fin', 'professional-booking-system'); ?></th>
                                <th><?php _e('Activo', 'professional-booking-system'); ?></th>
                                <th><?php _e('Acciones', 'professional-booking-system'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($schedules as $schedule): ?>
                                <tr>
                                    <td><?php echo esc_html(date_i18n(get_option('time_format'), strtotime($schedule->start_time))); ?></td>
                                    <td><?php echo esc_html(date_i18n(get_option('time_format'), strtotime($schedule->end_time))); ?></td>
                                    <td><?php echo $schedule->is_active ? __('Sí', 'professional-booking-system') : __('No', 'professional-booking-system'); ?></td>
                                    <td>
                                        <button class="button button-small pbs-delete-schedule" data-id="<?php echo esc_attr($schedule->id); ?>"><?php _e('Eliminar', 'professional-booking-system'); ?></button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p><em><?php _e('Sin horarios configurados', 'professional-booking-system'); ?></em></p>
                <?php endif; ?>
            <?php endforeach; ?>

            <!-- Modal para agregar horario -->
            <div id="pbs-schedule-modal" class="pbs-modal" style="display:none;">
                <div class="pbs-modal-content">
                    <span class="pbs-modal-close">&times;</span>
                    <h2><?php _e('Agregar Horario', 'professional-booking-system'); ?></h2>
                    <form id="pbs-schedule-form">
                        <p>
                            <label><?php _e('Día de la semana', 'professional-booking-system'); ?> *</label>
                            <select name="day_of_week" required>
                                <?php foreach ($days as $num => $name): ?>
                                    <option value="<?php echo esc_attr($num); ?>"><?php echo esc_html($name); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </p>

                        <p>
                            <label><?php _e('Hora de inicio', 'professional-booking-system'); ?> *</label>
                            <input type="time" name="start_time" required>
                        </p>

                        <p>
                            <label><?php _e('Hora de fin', 'professional-booking-system'); ?> *</label>
                            <input type="time" name="end_time" required>
                        </p>

                        <p>
                            <label>
                                <input type="checkbox" name="is_active" value="1" checked>
                                <?php _e('Activo', 'professional-booking-system'); ?>
                            </label>
                        </p>

                        <p>
                            <button type="submit" class="button button-primary"><?php _e('Guardar', 'professional-booking-system'); ?></button>
                            <button type="button" class="button pbs-modal-close"><?php _e('Cancelar', 'professional-booking-system'); ?></button>
                        </p>
                    </form>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Renderizar página de Excepciones
     */
    public function render_exceptions_page() {
        global $wpdb;

        $table_exceptions = $wpdb->prefix . 'pbs_exceptions';
        $exceptions = $wpdb->get_results("SELECT * FROM $table_exceptions ORDER BY exception_date DESC");

        ?>
        <div class="wrap">
            <h1><?php _e('Días Bloqueados y Excepciones', 'professional-booking-system'); ?>
                <button class="page-title-action" id="pbs-add-exception-btn"><?php _e('Agregar Excepción', 'professional-booking-system'); ?></button>
            </h1>

            <?php if ($exceptions): ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Fecha', 'professional-booking-system'); ?></th>
                            <th><?php _e('Tipo', 'professional-booking-system'); ?></th>
                            <th><?php _e('Horario', 'professional-booking-system'); ?></th>
                            <th><?php _e('Motivo', 'professional-booking-system'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($exceptions as $exception): ?>
                            <tr>
                                <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($exception->exception_date))); ?></td>
                                <td><?php echo esc_html(ucfirst($exception->type)); ?></td>
                                <td>
                                    <?php 
                                    if ($exception->start_time && $exception->end_time) {
                                        echo esc_html($exception->start_time . ' - ' . $exception->end_time);
                                    } else {
                                        _e('Todo el día', 'professional-booking-system');
                                    }
                                    ?>
                                </td>
                                <td><?php echo esc_html($exception->reason); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p><?php _e('No hay excepciones configuradas.', 'professional-booking-system'); ?></p>
            <?php endif; ?>

            <!-- Modal para agregar excepción -->
            <div id="pbs-exception-modal" class="pbs-modal" style="display:none;">
                <div class="pbs-modal-content">
                    <span class="pbs-modal-close">&times;</span>
                    <h2><?php _e('Agregar Excepción', 'professional-booking-system'); ?></h2>
                    <form id="pbs-exception-form">
                        <p>
                            <label><?php _e('Fecha', 'professional-booking-system'); ?> *</label>
                            <input type="date" name="exception_date" required>
                        </p>

                        <p>
                            <label><?php _e('Tipo', 'professional-booking-system'); ?> *</label>
                            <select name="type" required>
                                <option value="blocked"><?php _e('Bloqueado', 'professional-booking-system'); ?></option>
                                <option value="vacation"><?php _e('Vacaciones', 'professional-booking-system'); ?></option>
                                <option value="holiday"><?php _e('Feriado', 'professional-booking-system'); ?></option>
                            </select>
                        </p>

                        <p>
                            <label>
                                <input type="checkbox" id="pbs-exception-fullday" checked>
                                <?php _e('Bloquear todo el día', 'professional-booking-system'); ?>
                            </label>
                        </p>

                        <div id="pbs-exception-time-range" style="display:none;">
                            <p>
                                <label><?php _e('Hora de inicio', 'professional-booking-system'); ?></label>
                                <input type="time" name="start_time">
                            </p>

                            <p>
                                <label><?php _e('Hora de fin', 'professional-booking-system'); ?></label>
                                <input type="time" name="end_time">
                            </p>
                        </div>

                        <p>
                            <label><?php _e('Motivo', 'professional-booking-system'); ?></label>
                            <textarea name="reason" rows="3" class="widefat"></textarea>
                        </p>

                        <p>
                            <button type="submit" class="button button-primary"><?php _e('Guardar', 'professional-booking-system'); ?></button>
                            <button type="button" class="button pbs-modal-close"><?php _e('Cancelar', 'professional-booking-system'); ?></button>
                        </p>
                    </form>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Renderizar página de Configuración
     */
    public function render_settings_page() {
        $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'general';

        ?>
        <div class="wrap">
            <h1><?php _e('Configuración - Professional Booking System', 'professional-booking-system'); ?></h1>

            <h2 class="nav-tab-wrapper">
                <a href="?page=professional-booking-settings&tab=general" class="nav-tab <?php echo $active_tab === 'general' ? 'nav-tab-active' : ''; ?>"><?php _e('General', 'professional-booking-system'); ?></a>
                <a href="?page=professional-booking-settings&tab=payments" class="nav-tab <?php echo $active_tab === 'payments' ? 'nav-tab-active' : ''; ?>"><?php _e('Pagos', 'professional-booking-system'); ?></a>
                <a href="?page=professional-booking-settings&tab=videocalls" class="nav-tab <?php echo $active_tab === 'videocalls' ? 'nav-tab-active' : ''; ?>"><?php _e('Videollamadas', 'professional-booking-system'); ?></a>
                <a href="?page=professional-booking-settings&tab=notifications" class="nav-tab <?php echo $active_tab === 'notifications' ? 'nav-tab-active' : ''; ?>"><?php _e('Notificaciones', 'professional-booking-system'); ?></a>
                <a href="?page=professional-booking-settings&tab=google_calendar" class="nav-tab <?php echo $active_tab === 'google_calendar' ? 'nav-tab-active' : ''; ?>"><?php _e('Google Calendar', 'professional-booking-system'); ?></a>
            </h2>

            <form method="post" action="options.php">
                <?php
                switch ($active_tab) {
                    case 'general':
                        settings_fields('pbs_general_settings');
                        $this->render_general_settings();
                        break;
                    case 'payments':
                        settings_fields('pbs_payment_settings');
                        $this->render_payment_settings();
                        break;
                    case 'videocalls':
                        settings_fields('pbs_videocall_settings');
                        $this->render_videocall_settings();
                        break;
                    case 'notifications':
                        settings_fields('pbs_notification_settings');
                        $this->render_notification_settings();
                        break;
                    case 'google_calendar':
                        settings_fields('pbs_settings_group');
                        $this->render_google_calendar_settings();
                        break;
                }
                submit_button();
                ?>
            </form>


        </div>
        <?php
    }

    /**
     * Renderizar configuración general
     */
    private function render_general_settings() {
        ?>
        <table class="form-table">
            <tr>
                <th scope="row"><label for="pbs_professional_name"><?php _e('Nombre del Profesional', 'professional-booking-system'); ?></label></th>
                <td><input type="text" id="pbs_professional_name" name="pbs_professional_name" value="<?php echo esc_attr(get_option('pbs_professional_name')); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th scope="row"><label for="pbs_professional_specialty"><?php _e('Especialidad', 'professional-booking-system'); ?></label></th>
                <td><input type="text" id="pbs_professional_specialty" name="pbs_professional_specialty" value="<?php echo esc_attr(get_option('pbs_professional_specialty')); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th scope="row"><label for="pbs_default_duration"><?php _e('Duración predeterminada (minutos)', 'professional-booking-system'); ?></label></th>
                <td>
                    <select id="pbs_default_duration" name="pbs_default_duration">
                        <option value="15" <?php selected(get_option('pbs_default_duration'), 15); ?>>15</option>
                        <option value="30" <?php selected(get_option('pbs_default_duration'), 30); ?>>30</option>
                        <option value="45" <?php selected(get_option('pbs_default_duration'), 45); ?>>45</option>
                        <option value="60" <?php selected(get_option('pbs_default_duration'), 60); ?>>60</option>
                        <option value="90" <?php selected(get_option('pbs_default_duration'), 90); ?>>90</option>
                        <option value="120" <?php selected(get_option('pbs_default_duration'), 120); ?>>120</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="pbs_buffer_time"><?php _e('Tiempo de buffer entre turnos (minutos)', 'professional-booking-system'); ?></label></th>
                <td>
                    <select id="pbs_buffer_time" name="pbs_buffer_time">
                        <option value="0" <?php selected(get_option('pbs_buffer_time'), 0); ?>>0</option>
                        <option value="5" <?php selected(get_option('pbs_buffer_time'), 5); ?>>5</option>
                        <option value="10" <?php selected(get_option('pbs_buffer_time'), 10); ?>>10</option>
                        <option value="15" <?php selected(get_option('pbs_buffer_time'), 15); ?>>15</option>
                        <option value="30" <?php selected(get_option('pbs_buffer_time'), 30); ?>>30</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="pbs_min_booking_notice"><?php _e('Anticipación mínima para reservar (días)', 'professional-booking-system'); ?></label></th>
                <td><input type="number" id="pbs_min_booking_notice" name="pbs_min_booking_notice" value="<?php echo esc_attr(get_option('pbs_min_booking_notice', 1)); ?>" min="0" max="30"></td>
            </tr>
            <tr>
                <th scope="row"><label for="pbs_max_booking_notice"><?php _e('Anticipación máxima para reservar (días)', 'professional-booking-system'); ?></label></th>
                <td><input type="number" id="pbs_max_booking_notice" name="pbs_max_booking_notice" value="<?php echo esc_attr(get_option('pbs_max_booking_notice', 90)); ?>" min="1" max="365"></td>
            </tr>
            <tr>
                <th scope="row"><label for="pbs_timezone"><?php _e('Zona horaria', 'professional-booking-system'); ?></label></th>
                <td>
                    <select id="pbs_timezone" name="pbs_timezone">
                        <?php
                        $timezones = timezone_identifiers_list();
                        $current_tz = get_option('pbs_timezone', wp_timezone_string());
                        foreach ($timezones as $tz) {
                            echo '<option value="' . esc_attr($tz) . '" ' . selected($current_tz, $tz, false) . '>' . esc_html($tz) . '</option>';
                        }
                        ?>
                    </select>
                </td>
            </tr>

            
        </table>
        <?php
    }

    /**
     * Renderizar configuración de pagos
     */
    private function render_payment_settings() {
        ?>
        <table class="form-table">
            <tr>
                <th scope="row"><label for="pbs_currency"><?php _e('Moneda', 'professional-booking-system'); ?></label></th>
                <td>
                    <select id="pbs_currency" name="pbs_currency">
                        <option value="USD" <?php selected(get_option('pbs_currency'), 'USD'); ?>>USD</option>
                        <option value="EUR" <?php selected(get_option('pbs_currency'), 'EUR'); ?>>EUR</option>
                        <option value="GBP" <?php selected(get_option('pbs_currency'), 'GBP'); ?>>GBP</option>
                        <option value="ARS" <?php selected(get_option('pbs_currency'), 'ARS'); ?>>ARS</option>
                        <option value="MXN" <?php selected(get_option('pbs_currency'), 'MXN'); ?>>MXN</option>
                        <option value="CLP" <?php selected(get_option('pbs_currency'), 'CLP'); ?>>CLP</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><label><?php _e('Modo de prueba Stripe', 'professional-booking-system'); ?></label></th>
                <td>
                    <label>
                        <input type="checkbox" name="pbs_stripe_test_mode" value="1" <?php checked(get_option('pbs_stripe_test_mode'), 1); ?>>
                        <?php _e('Activar modo de prueba', 'professional-booking-system'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="pbs_stripe_test_publishable_key"><?php _e('Stripe Test Publishable Key', 'professional-booking-system'); ?></label></th>
                <td><input type="text" id="pbs_stripe_test_publishable_key" name="pbs_stripe_test_publishable_key" value="<?php echo esc_attr(get_option('pbs_stripe_test_publishable_key')); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th scope="row"><label for="pbs_stripe_test_secret_key"><?php _e('Stripe Test Secret Key', 'professional-booking-system'); ?></label></th>
                <td><input type="password" id="pbs_stripe_test_secret_key" name="pbs_stripe_test_secret_key" value="<?php echo esc_attr(get_option('pbs_stripe_test_secret_key')); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th scope="row"><label for="pbs_stripe_live_publishable_key"><?php _e('Stripe Live Publishable Key', 'professional-booking-system'); ?></label></th>
                <td><input type="text" id="pbs_stripe_live_publishable_key" name="pbs_stripe_live_publishable_key" value="<?php echo esc_attr(get_option('pbs_stripe_live_publishable_key')); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th scope="row"><label for="pbs_stripe_live_secret_key"><?php _e('Stripe Live Secret Key', 'professional-booking-system'); ?></label></th>
                <td><input type="password" id="pbs_stripe_live_secret_key" name="pbs_stripe_live_secret_key" value="<?php echo esc_attr(get_option('pbs_stripe_live_secret_key')); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th scope="row"><label for="pbs_require_payment"><?php _e('Tipo de pago requerido', 'professional-booking-system'); ?></label></th>
                <td>
                    <select id="pbs_require_payment" name="pbs_require_payment">
                        <option value="full" <?php selected(get_option('pbs_require_payment'), 'full'); ?>><?php _e('Pago completo', 'professional-booking-system'); ?></option>
                        <option value="partial" <?php selected(get_option('pbs_require_payment'), 'partial'); ?>><?php _e('Señal/Depósito', 'professional-booking-system'); ?></option>
                        <option value="none" <?php selected(get_option('pbs_require_payment'), 'none'); ?>><?php _e('Sin pago online', 'professional-booking-system'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="pbs_payment_percentage"><?php _e('Porcentaje de señal (%)', 'professional-booking-system'); ?></label></th>
                <td><input type="number" id="pbs_payment_percentage" name="pbs_payment_percentage" value="<?php echo esc_attr(get_option('pbs_payment_percentage', 50)); ?>" min="1" max="100"></td>
            </tr>
            <tr>
                <th scope="row"><label for="pbs_cancellation_hours"><?php _e('Horas de anticipación para cancelar', 'professional-booking-system'); ?></label></th>
                <td><input type="number" id="pbs_cancellation_hours" name="pbs_cancellation_hours" value="<?php echo esc_attr(get_option('pbs_cancellation_hours', 24)); ?>" min="0"></td>
            </tr>

            <tr>
                <th scope="row"><?php _e( 'Payment Provider', 'professional-booking-system' ); ?></th>
                <td>
                    <select name="pbs_payment_provider">
                    <option value="disabled" <?php selected( get_option( 'pbs_payment_provider', 'disabled' ), 'disabled' ); ?>>
                        <?php _e( 'Disabled', 'professional-booking-system' ); ?>
                    </option>
                    <option value="mercadopago" <?php selected( get_option( 'pbs_payment_provider' ), 'mercadopago' ); ?>>
                        <?php _e( 'MercadoPago (Argentina)', 'professional-booking-system' ); ?>
                    </option>
                    <option value="stripe" <?php selected( get_option( 'pbs_payment_provider' ), 'stripe' ); ?>>
                        <?php _e( 'Stripe', 'professional-booking-system' ); ?>
                    </option>
                    <option value="paypal" <?php selected( get_option( 'pbs_payment_provider' ), 'paypal' ); ?>>
                        <?php _e( 'PayPal', 'professional-booking-system' ); ?>
                    </option>
                    </select>
                </td>
                </tr>

                <tr>
                <th scope="row"><?php _e( 'PayPal Client ID', 'professional-booking-system' ); ?></th>
                <td><input type="text" name="pbs_paypal_client_id" value="<?php echo esc_attr( get_option( 'pbs_paypal_client_id', '' ) ); ?>" class="regular-text"></td>
                </tr>

                <tr>
                <th scope="row"><?php _e( 'PayPal Secret', 'professional-booking-system' ); ?></th>
                <td><input type="text" name="pbs_paypal_secret" value="<?php echo esc_attr( get_option( 'pbs_paypal_secret', '' ) ); ?>" class="regular-text"></td>
                </tr>

                <tr>
                <th scope="row"><?php _e( 'PayPal Mode', 'professional-booking-system' ); ?></th>
                <td>
                    <select name="pbs_paypal_mode">
                    <option value="sandbox" <?php selected( get_option( 'pbs_paypal_mode', 'sandbox' ), 'sandbox' ); ?>>
                        <?php _e( 'Sandbox', 'professional-booking-system' ); ?>
                    </option>
                    <option value="live" <?php selected( get_option( 'pbs_paypal_mode', 'sandbox' ), 'live' ); ?>>
                        <?php _e( 'Live', 'professional-booking-system' ); ?>
                    </option>
                    </select>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Renderizar configuración de videollamadas
     */
    private function render_videocall_settings() {
        ?>
        <table class="form-table">
            <tr>
                <th scope="row"><label><?php _e('Habilitar videollamadas', 'professional-booking-system'); ?></label></th>
                <td>
                    <label>
                        <input type="checkbox" name="pbs_enable_videocalls" value="yes" <?php checked(get_option('pbs_enable_videocalls'), 'yes'); ?>>
                        <?php _e('Activar funcionalidad de videollamadas', 'professional-booking-system'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="pbs_google_client_id"><?php _e('Google Client ID', 'professional-booking-system'); ?></label></th>
                <td>
                    <input type="text" id="pbs_google_client_id" name="pbs_google_client_id" value="<?php echo esc_attr(get_option('pbs_google_client_id')); ?>" class="regular-text">
                    <p class="description"><?php _e('Para integración con Google Calendar y Google Meet', 'professional-booking-system'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="pbs_google_client_secret"><?php _e('Google Client Secret', 'professional-booking-system'); ?></label></th>
                <td><input type="password" id="pbs_google_client_secret" name="pbs_google_client_secret" value="<?php echo esc_attr(get_option('pbs_google_client_secret')); ?>" class="regular-text"></td>
            </tr>

            <tr>
                <th scope="row"><?php _e( 'Enable Google Calendar Sync', 'professional-booking-system' ); ?></th>
                <td>
                    <label>
                    <input type="checkbox" name="pbs_gcal_enabled" value="1" <?php checked( get_option( 'pbs_gcal_enabled', '0' ), '1' ); ?>>
                    <?php _e( 'Create Google Calendar events for confirmed bookings', 'professional-booking-system' ); ?>
                    </label>
                </td>
                </tr>

                <tr>
                <th scope="row"><?php _e( 'Calendar ID', 'professional-booking-system' ); ?></th>
                <td>
                    <input type="text" name="pbs_gcal_calendar_id" value="<?php echo esc_attr( get_option( 'pbs_gcal_calendar_id', 'primary' ) ); ?>" class="regular-text">
                    <p class="description"><?php _e( 'Use "primary" or a specific calendar ID (e.g. your_email@gmail.com).', 'professional-booking-system' ); ?></p>
                </td>
                </tr>

                <tr>
                <th scope="row"><?php _e( 'Client ID', 'professional-booking-system' ); ?></th>
                <td><input type="text" name="pbs_gcal_client_id" value="<?php echo esc_attr( get_option( 'pbs_gcal_client_id', '' ) ); ?>" class="regular-text"></td>
                </tr>

                <tr>
                <th scope="row"><?php _e( 'Client Secret', 'professional-booking-system' ); ?></th>
                <td><input type="password" name="pbs_gcal_client_secret" value="<?php echo esc_attr( get_option( 'pbs_gcal_client_secret', '' ) ); ?>" class="regular-text"></td>
                </tr>

                <tr>
                <th scope="row"><?php _e( 'Refresh Token', 'professional-booking-system' ); ?></th>
                <td>
                    <input type="text" name="pbs_gcal_refresh_token" value="<?php echo esc_attr( get_option( 'pbs_gcal_refresh_token', '' ) ); ?>" class="regular-text">
                    <p class="description"><?php _e( 'OAuth2 refresh token with access to the selected calendar.', 'professional-booking-system' ); ?></p>
                </td>
                </tr>

                <tr>
                <th scope="row"><?php _e( 'Time Zone', 'professional-booking-system' ); ?></th>
                <td>
                    <input type="text" name="pbs_gcal_timezone" value="<?php echo esc_attr( get_option( 'pbs_gcal_timezone', get_option( 'timezone_string', 'UTC' ) ) ); ?>" class="regular-text">
                    <p class="description"><?php _e( 'e.g. America/Argentina/Buenos_Aires', 'professional-booking-system' ); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e( 'Create Google Meet link', 'professional-booking-system' ); ?></th>
                <td>
                    <label>
                    <input type="checkbox" name="pbs_gcal_meet_enabled" value="1" <?php checked( get_option( 'pbs_gcal_meet_enabled', '0' ), '1' ); ?>>
                    <?php _e( 'Automatically create a Google Meet link for each event', 'professional-booking-system' ); ?>
                    </label>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Renderizar configuración de notificaciones
     */
    private function render_notification_settings() {
        ?>
        <h2><?php _e( 'Email Notifications', 'professional-booking-system' ); ?></h2>

        <table class="form-table">
            <tr>
                <th scope="row"><?php _e( 'From Name', 'professional-booking-system' ); ?></th>
                <td>
                <input type="text" name="pbs_email_from_name" value="<?php echo esc_attr( get_option( 'pbs_email_from_name', get_bloginfo( 'name' ) ) ); ?>" class="regular-text">
                </td>
            </tr>

            <tr>
                <th scope="row"><?php _e( 'From Email', 'professional-booking-system' ); ?></th>
                <td>
                <input type="email" name="pbs_email_from_address" value="<?php echo esc_attr( get_option( 'pbs_email_from_address', get_option( 'admin_email' ) ) ); ?>" class="regular-text">
                </td>
            </tr>

            <tr>
                <th scope="row"><?php _e( 'Send email to client', 'professional-booking-system' ); ?></th>
                <td>
                <label>
                    <input type="checkbox" name="pbs_email_send_client" value="1" <?php checked( get_option( 'pbs_email_send_client', '1' ), '1' ); ?>>
                    <?php _e( 'Send confirmation email to the client when the booking is confirmed', 'professional-booking-system' ); ?>
                </label>
                </td>
            </tr>

            <tr>
                <th scope="row"><?php _e( 'Send email to admin/professional', 'professional-booking-system' ); ?></th>
                <td>
                <label>
                    <input type="checkbox" name="pbs_email_send_admin" value="1" <?php checked( get_option( 'pbs_email_send_admin', '1' ), '1' ); ?>>
                    <?php _e( 'Notify the admin/professional on each confirmed booking', 'professional-booking-system' ); ?>
                </label>
                </td>
            </tr>

            <tr>
                <th scope="row"><?php _e( 'Admin Email', 'professional-booking-system' ); ?></th>
                <td>
                <input type="email" name="pbs_email_admin_address" value="<?php echo esc_attr( get_option( 'pbs_email_admin_address', get_option( 'admin_email' ) ) ); ?>" class="regular-text">
                </td>
            </tr>

            <tr>
                <th scope="row"><?php _e( 'Client Email Subject', 'professional-booking-system' ); ?></th>
                <td>
                <input type="text" name="pbs_email_client_subject" value="<?php echo esc_attr( get_option( 'pbs_email_client_subject', __( 'Your appointment is confirmed', 'professional-booking-system' ) ) ); ?>" class="regular-text">
                </td>
            </tr>

            <tr>
                <th scope="row"><?php _e( 'Admin Email Subject', 'professional-booking-system' ); ?></th>
                <td>
                <input type="text" name="pbs_email_admin_subject" value="<?php echo esc_attr( get_option( 'pbs_email_admin_subject', __( 'New confirmed booking', 'professional-booking-system' ) ) ); ?>" class="regular-text">
                </td>
            </tr>

            <tr>
                <th scope="row"><?php _e( 'Client Email Template', 'professional-booking-system' ); ?></th>
                <td>
                <textarea name="pbs_email_client_template" rows="8" class="large-text code"><?php
                    $default_client_tpl = "Hola {{client_name}},\n\nTu turno está confirmado.\n\nServicio: {{service_name}}\nFecha: {{date}}\nHora: {{time}}\nProfesional: {{site_name}}\n\nEnlace de videollamada: {{video_link}}\n\nGracias,\n{{site_name}}";
                    echo esc_textarea( get_option( 'pbs_email_client_template', $default_client_tpl ) );
                ?></textarea>
                <p class="description">
                    <?php _e( 'Placeholders available: {{client_name}}, {{client_email}}, {{service_name}}, {{date}}, {{time}}, {{video_link}}, {{booking_id}}, {{site_name}}', 'professional-booking-system' ); ?>
                </p>
                </td>
            </tr>

            <tr>
                <th scope="row"><?php _e( 'Admin Email Template', 'professional-booking-system' ); ?></th>
                <td>
                <textarea name="pbs_email_admin_template" rows="8" class="large-text code"><?php
                    $default_admin_tpl = "Nuevo turno confirmado.\n\nCliente: {{client_name}} ({{client_email}})\nServicio: {{service_name}}\nFecha: {{date}}\nHora: {{time}}\nID de reserva: {{booking_id}}\nVideollamada: {{video_link}}\n\nSitio: {{site_name}}";
                    echo esc_textarea( get_option( 'pbs_email_admin_template', $default_admin_tpl ) );
                ?></textarea>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * AJAX: Guardar servicio
     */
    public function ajax_save_service() {
        check_ajax_referer('pbs_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permisos insuficientes', 'professional-booking-system')));
        }

        $service_id = isset($_POST['service_id']) ? intval($_POST['service_id']) : 0;
        $data = array(
            'name'             => sanitize_text_field($_POST['name']),
            'description'      => wp_kses_post($_POST['description']),
            'duration'         => intval($_POST['duration']),
            'price'            => floatval($_POST['price']),
            'enable_videocall' => isset($_POST['enable_videocall']) ? 1 : 0,
            'category'         => sanitize_text_field($_POST['category']),
        );

        if ($service_id > 0) {
            $result = PBS_Services::update($service_id, $data);
        } else {
            $result = PBS_Services::create($data);
        }

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }

        wp_send_json_success(array('message' => __('Servicio guardado correctamente', 'professional-booking-system')));
    }

    /**
     * AJAX: Eliminar servicio
     */
    public function ajax_delete_service() {
        check_ajax_referer('pbs_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permisos insuficientes', 'professional-booking-system')));
        }

        $service_id = intval($_POST['service_id']);
        $result = PBS_Services::delete($service_id);

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }

        wp_send_json_success(array('message' => __('Servicio eliminado correctamente', 'professional-booking-system')));
    }

    /**
     * AJAX: Guardar horario
     */
    public function ajax_save_schedule() {
        check_ajax_referer('pbs_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permisos insuficientes', 'professional-booking-system')));
        }

        $data = array(
            'day_of_week' => intval($_POST['day_of_week']),
            'start_time'  => sanitize_text_field($_POST['start_time']),
            'end_time'    => sanitize_text_field($_POST['end_time']),
            'is_active'   => isset($_POST['is_active']) ? 1 : 0,
        );

        $result = PBS_Schedules::create_schedule($data);

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }

        wp_send_json_success(array('message' => __('Horario guardado correctamente', 'professional-booking-system')));
    }

    /**
     * AJAX: Eliminar horario
     */
    public function ajax_delete_schedule() {
        check_ajax_referer('pbs_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permisos insuficientes', 'professional-booking-system')));
        }

        $schedule_id = intval($_POST['schedule_id']);
        $result = PBS_Schedules::delete_schedule($schedule_id);

        if (!$result) {
            wp_send_json_error(array('message' => __('No se pudo eliminar el horario', 'professional-booking-system')));
        }

        wp_send_json_success(array('message' => __('Horario eliminado correctamente', 'professional-booking-system')));
    }

    /**
     * AJAX: Guardar excepción
     */
    public function ajax_save_exception() {
        check_ajax_referer('pbs_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permisos insuficientes', 'professional-booking-system')));
        }

        $data = array(
            'exception_date' => sanitize_text_field($_POST['exception_date']),
            'start_time'     => !empty($_POST['start_time']) ? sanitize_text_field($_POST['start_time']) : null,
            'end_time'       => !empty($_POST['end_time']) ? sanitize_text_field($_POST['end_time']) : null,
            'type'           => sanitize_text_field($_POST['type']),
            'reason'         => wp_kses_post($_POST['reason']),
        );

        $result = PBS_Schedules::create_exception($data);

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }

        wp_send_json_success(array('message' => __('Excepción guardada correctamente', 'professional-booking-system')));
    }

    /**
     * AJAX: Actualizar estado de reserva
     */
    public function ajax_update_booking_status() {
        check_ajax_referer('pbs_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permisos insuficientes', 'professional-booking-system')));
        }

        $booking_id = intval($_POST['booking_id']);
        $status = sanitize_text_field($_POST['status']);

        $result = PBS_Bookings::update_status($booking_id, $status);

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }

        wp_send_json_success(array('message' => __('Estado actualizado correctamente', 'professional-booking-system')));
    }

    public function ajax_get_booking_detail() {
        check_ajax_referer( 'pbs_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Unauthorized' ), 403 );
        }

        $booking_id = isset( $_POST['booking_id'] ) ? intval( $_POST['booking_id'] ) : 0;
        if ( ! $booking_id ) {
            wp_send_json_error( array( 'message' => 'Invalid booking ID' ), 400 );
        }

        $booking = PBS_Bookings::get_booking( $booking_id );
        if ( ! $booking ) {
            wp_send_json_error( array( 'message' => 'Booking not found' ), 404 );
        }

        $service = PBS_Services::get_service( $booking['service_id'] );

        ob_start();
        ?>
        <div class="pbs-booking-detail">
            <p><strong><?php _e( 'Booking ID:', 'professional-booking-system' ); ?></strong> #<?php echo esc_html( $booking['id'] ); ?></p>
            <p><strong><?php _e( 'Service:', 'professional-booking-system' ); ?></strong> <?php echo esc_html( $service ? $service['name'] : '' ); ?></p>
            <p><strong><?php _e( 'Date:', 'professional-booking-system' ); ?></strong> <?php echo esc_html( $booking['booking_date'] ); ?></p>
            <p><strong><?php _e( 'Time:', 'professional-booking-system' ); ?></strong> <?php echo esc_html( substr( $booking['booking_time'], 0, 5 ) ); ?></p>
            <p><strong><?php _e( 'Client:', 'professional-booking-system' ); ?></strong> <?php echo esc_html( $booking['customer_name'] ); ?></p>
            <p><strong><?php _e( 'Email:', 'professional-booking-system' ); ?></strong> <?php echo esc_html( $booking['customer_email'] ); ?></p>
            <?php if ( ! empty( $booking['customer_phone'] ) ) : ?>
                <p><strong><?php _e( 'Phone:', 'professional-booking-system' ); ?></strong> <?php echo esc_html( $booking['customer_phone'] ); ?></p>
            <?php endif; ?>
            <?php if ( ! empty( $booking['customer_notes'] ) ) : ?>
                <p><strong><?php _e( 'Notes:', 'professional-booking-system' ); ?></strong><br><?php echo nl2br( esc_html( $booking['customer_notes'] ) ); ?></p>
            <?php endif; ?>

            <p><strong><?php _e( 'Status:', 'professional-booking-system' ); ?></strong>
                <span class="pbs-status-badge pbs-status-<?php echo esc_attr( $booking['status'] ); ?>">
                    <?php echo esc_html( ucfirst( $booking['status'] ) ); ?>
                </span>
            </p>
            <p><strong><?php _e( 'Payment:', 'professional-booking-system' ); ?></strong>
                <span class="pbs-status-badge pbs-payment-<?php echo esc_attr( $booking['payment_status'] ); ?>">
                    <?php echo esc_html( ucfirst( $booking['payment_status'] ) ); ?>
                </span>
            </p>

            <?php if ( ! empty( $booking['videocall_link'] ) ) : ?>
                <p><strong><?php _e( 'Video call:', 'professional-booking-system' ); ?></strong>
                    <a href="<?php echo esc_url( $booking['videocall_link'] ); ?>" target="_blank">
                        <?php _e( 'Join Google Meet', 'professional-booking-system' ); ?>
                    </a>
                </p>
            <?php endif; ?>

            <?php if ( ! empty( $booking['google_event_id'] ) ) : ?>
                <p><strong><?php _e( 'Google Calendar Event ID:', 'professional-booking-system' ); ?></strong> <?php echo esc_html( $booking['google_event_id'] ); ?></p>
            <?php endif; ?>
        </div>
        <?php
        $html = ob_get_clean();

        wp_send_json_success( array( 'html' => $html ) );
    }

    /**
     * AJAX handler para desconectar Google Calendar
     */
    public function ajax_disconnect_google() {
        check_ajax_referer('pbs_disconnect_google', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('No tienes permiso para realizar esta acción', 'professional-booking-system')), 403);
        }

        // Eliminar todas las opciones de Google Calendar
        delete_option('pbs_gcal_enabled');
        delete_option('pbs_gcal_client_id');
        delete_option('pbs_gcal_client_secret');
        delete_option('pbs_gcal_calendar_id');
        delete_option('pbs_gcal_refresh_token');
        delete_option('pbs_gcal_timezone');
        delete_option('pbs_gcal_create_meet');
        delete_option('pbs_gcal_authorized_email');

        error_log('[PBS] Google Calendar desconectado');
        wp_send_json_success(array('message' => __('Desconectado de Google Calendar', 'professional-booking-system')));
    }

    /**
     * AJAX handler para generar/regenerar Google Meet link
     */
    public function ajax_generate_meet() {
        error_log('[PBS] ajax_generate_meet called with POST data: ' . wp_json_encode($_POST));
        
        // Verificar nonce con mejor manejo de errores
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'pbs_admin_nonce' ) ) {
            error_log('[PBS] Invalid nonce for ajax_generate_meet');
            wp_send_json_error( array( 'message' => __( 'Nonce verification failed', 'professional-booking-system' ) ) );
            return;
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('No tienes permiso para realizar esta acción', 'professional-booking-system')));
        }

        $booking_id = isset($_POST['booking_id']) ? intval($_POST['booking_id']) : 0;
        if ($booking_id <= 0) {
            wp_send_json_error(array('message' => __('ID de reserva inválido', 'professional-booking-system')));
        }

        // Obtener la reserva
        $booking = PBS_Bookings::get_booking($booking_id);
        if (!$booking) {
            wp_send_json_error(array('message' => __('Reserva no encontrada', 'professional-booking-system')));
        }

        // Obtener el servicio
        $service = PBS_Services::get_service($booking['service_id']);
        if (!$service) {
            wp_send_json_error(array('message' => __('Servicio no encontrado', 'professional-booking-system')));
        }

        // Verificar si Google Calendar está habilitado
        if (!PBS_Google_Calendar::get_instance()->is_enabled()) {
            wp_send_json_error(array('message' => __('Google Calendar no está habilitado. Por favor, configúralo en Reservas → Configuración → Google Calendar', 'professional-booking-system')));
        }

        try {
            // Crear o actualizar evento en Google Calendar
            $result = PBS_Google_Calendar::get_instance()->create_event_for_booking($booking, $service);
            
            if (!$result['success']) {
                $error_msg = isset($result['error']) ? $result['error'] : 'Unknown error';
                wp_send_json_error(array('message' => __('Error al crear evento en Google Calendar: ', 'professional-booking-system') . $error_msg));
            }

            // Actualizar la reserva con el event_id y meet_link
            global $wpdb;
            $update_data = array(
                'google_event_id' => $result['event_id'],
            );
            if ( ! empty( $result['meet_link'] ) ) {
                $update_data['videocall_link'] = $result['meet_link'];
            }

            $wpdb->update(
                PBS_Bookings::get_table_bookings(),
                $update_data,
                array('id' => $booking_id),
                array_fill(0, count($update_data), '%s'),
                array('%d')
            );

            $logged_link = ! empty( $result['meet_link'] ) ? $result['meet_link'] : '';
            error_log('[PBS] Google Meet link generado para reserva #' . $booking_id . ': ' . $logged_link);
            
            wp_send_json_success(array(
                'message' => __('Google Meet link generado correctamente', 'professional-booking-system'),
                'meet_link' => $logged_link
            ));

        } catch (Exception $e) {
            error_log('[PBS] Error al generar Meet: ' . $e->getMessage());
            wp_send_json_error(array('message' => __('Error al generar Google Meet link: ', 'professional-booking-system') . $e->getMessage()));
        }
    }

    /**
     * Renderizar configuración de Google Calendar
     */
    private function render_google_calendar_settings() {
        $gcal_enabled = get_option('pbs_gcal_enabled');
        $gcal_client_id = get_option('pbs_gcal_client_id');
        $gcal_client_secret = get_option('pbs_gcal_client_secret');
        $gcal_calendar_id = get_option('pbs_gcal_calendar_id', 'primary');
        $gcal_timezone = get_option('pbs_gcal_timezone', wp_timezone_string());
        $gcal_create_meet = get_option('pbs_gcal_create_meet');
        $gcal_refresh_token = get_option('pbs_gcal_refresh_token');
        $gcal_authorized_email = get_option('pbs_gcal_authorized_email');
        ?>
        <div class="pbs-google-calendar-settings">
            <p><?php _e('Integra tu Google Calendar para sincronizar reservas automáticamente y evitar dobles reservas.', 'professional-booking-system'); ?></p>
            
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="pbs_gcal_enabled"><?php _e('Habilitar Google Calendar', 'professional-booking-system'); ?></label></th>
                    <td>
                        <label>
                            <input type="checkbox" id="pbs_gcal_enabled" name="pbs_gcal_enabled" value="1" <?php checked($gcal_enabled, 1); ?>>
                            <?php _e('Sincronizar reservas con Google Calendar', 'professional-booking-system'); ?>
                        </label>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="pbs_gcal_client_id"><?php _e('Client ID de Google', 'professional-booking-system'); ?></label></th>
                    <td>
                        <input type="text" id="pbs_gcal_client_id" name="pbs_gcal_client_id" value="<?php echo esc_attr($gcal_client_id); ?>" class="regular-text" placeholder="xxx.apps.googleusercontent.com">
                        <p class="description"><?php _e('Obtén este valor desde Google Cloud Console', 'professional-booking-system'); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="pbs_gcal_client_secret"><?php _e('Client Secret de Google', 'professional-booking-system'); ?></label></th>
                    <td>
                        <input type="password" id="pbs_gcal_client_secret" name="pbs_gcal_client_secret" value="<?php echo esc_attr($gcal_client_secret); ?>" class="regular-text">
                        <p class="description"><?php _e('Se guarda encriptado en la base de datos', 'professional-booking-system'); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="pbs_gcal_refresh_token"><?php _e('Refresh Token de Google', 'professional-booking-system'); ?></label></th>
                    <td>
                        <textarea id="pbs_gcal_refresh_token" name="pbs_gcal_refresh_token" class="large-text" rows="3" placeholder="1//0xxxxxxxxxxxxx..."><?php echo esc_textarea($gcal_refresh_token); ?></textarea>
                        <p class="description">
                            <?php _e('Obtén este token desde OAuth 2.0 Playground:', 'professional-booking-system'); ?><br>
                            <a href="https://developers.google.com/oauthplayground/" target="_blank">https://developers.google.com/oauthplayground/</a><br>
                            <?php _e('1. Selecciona "Calendar API v3" → Authorize APIs', 'professional-booking-system'); ?><br>
                            <?php _e('2. Haz clic en "Exchange authorization code for tokens"', 'professional-booking-system'); ?><br>
                            <?php _e('3. Copia el "Refresh token" y pégalo aquí', 'professional-booking-system'); ?>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="pbs_gcal_timezone"><?php _e('Zona horaria', 'professional-booking-system'); ?></label></th>
                    <td>
                        <select id="pbs_gcal_timezone" name="pbs_gcal_timezone">
                            <?php
                            $timezones = timezone_identifiers_list();
                            foreach ($timezones as $tz) {
                                echo '<option value="' . esc_attr($tz) . '" ' . selected($gcal_timezone, $tz, false) . '>' . esc_html($tz) . '</option>';
                            }
                            ?>
                        </select>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="pbs_gcal_calendar_id"><?php _e('Calendar ID', 'professional-booking-system'); ?></label></th>
                    <td>
                        <input type="text" id="pbs_gcal_calendar_id" name="pbs_gcal_calendar_id" value="<?php echo esc_attr($gcal_calendar_id); ?>" class="regular-text" placeholder="primary">
                        <p class="description"><?php _e('Por defecto usa tu calendario principal (primary)', 'professional-booking-system'); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="pbs_gcal_create_meet"><?php _e('Crear Google Meet', 'professional-booking-system'); ?></label></th>
                    <td>
                        <label>
                            <input type="checkbox" id="pbs_gcal_create_meet" name="pbs_gcal_create_meet" value="1" <?php checked($gcal_create_meet, 1); ?>>
                            <?php _e('Generar automáticamente enlace de Google Meet para cada reserva', 'professional-booking-system'); ?>
                        </label>
                    </td>
                </tr>

                <?php if ($gcal_authorized_email) : ?>
                    <tr>
                        <th scope="row"><?php _e('Estado de autenticación', 'professional-booking-system'); ?></th>
                        <td>
                            <div style="padding: 10px; background-color: #d4edda; border: 1px solid #c3e6cb; border-radius: 4px;">
                                <p style="margin: 0; color: #155724;">
                                    <strong><?php _e('✓ Conectado como:', 'professional-booking-system'); ?></strong> 
                                    <?php echo esc_html($gcal_authorized_email); ?>
                                </p>
                            </div>
                        </td>
                    </tr>
                <?php else : ?>
                    <tr>
                        <th scope="row"><?php _e('Estado', 'professional-booking-system'); ?></th>
                        <td>
                            <?php if (empty($gcal_refresh_token)) : ?>
                                <div style="padding: 10px; background-color: #fff3cd; border: 1px solid #ffeaa7; border-radius: 4px;">
                                    <p style="margin: 0; color: #856404;">
                                        <strong><?php _e('⚠️ Falta el Refresh Token', 'professional-booking-system'); ?></strong><br>
                                        <?php _e('Necesitas obtener un Refresh Token desde OAuth 2.0 Playground para completar la configuración.', 'professional-booking-system'); ?>
                                    </p>
                                </div>
                            <?php else : ?>
                                <div style="padding: 10px; background-color: #d4edda; border: 1px solid #c3e6cb; border-radius: 4px;">
                                    <p style="margin: 0; color: #155724;">
                                        <strong><?php _e('✓ Configuración completa', 'professional-booking-system'); ?></strong><br>
                                        <?php _e('Marca "Habilitar Google Calendar" arriba y guarda cambios.', 'professional-booking-system'); ?>
                                    </p>
                                </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </table>

            <?php if ($gcal_authorized_email) : ?>
                <p>
                    <a href="#" class="button" id="pbs-disconnect-google" style="background-color: #f8d7da; border-color: #f5c6cb; color: #721c24;">
                        <?php _e('Desconectar de Google', 'professional-booking-system'); ?>
                    </a>
                </p>
                <script>
                    jQuery('#pbs-disconnect-google').on('click', function(e) {
                        e.preventDefault();
                        if (confirm('<?php _e('¿Estás seguro? Se desconectará tu cuenta de Google.', 'professional-booking-system'); ?>')) {
                            var formData = new FormData();
                            formData.append('action', 'pbs_disconnect_google');
                            formData.append('nonce', '<?php echo wp_create_nonce('pbs_disconnect_google'); ?>');
                            
                            fetch(ajaxurl, {
                                method: 'POST',
                                body: formData
                            }).then(response => response.json()).then(data => {
                                if (data.success) {
                                    location.reload();
                                } else {
                                    alert(data.data.message || '<?php _e('Error al desconectar', 'professional-booking-system'); ?>');
                                }
                            });
                        }
                    });
                </script>
            <?php endif; ?>
        </div>
        <?php
    }
}
