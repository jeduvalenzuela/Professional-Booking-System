<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;

/**
 * Widget de Reservas para Elementor
 */
class PBS_Booking_Widget extends Widget_Base {

    /**
     * Get widget name
     */
    public function get_name() {
        return 'pbs_booking';
    }

    /**
     * Get widget title
     */
    public function get_title() {
        return __( 'Professional Booking', 'professional-booking-system' );
    }

    /**
     * Get widget icon
     */
    public function get_icon() {
        return 'eicon-calendar';
    }

    /**
     * Get widget categories
     */
    public function get_categories() {
        return array( 'general' );
    }

    /**
     * Get widget keywords
     */
    public function get_keywords() {
        return array( 'booking', 'appointment', 'calendar', 'reservation' );
    }

    /**
     * Register widget controls
     */
    protected function register_controls() {

        // ========== CONTENIDO ==========
        $this->start_controls_section(
            'content_section',
            array(
                'label' => __( 'Content', 'professional-booking-system' ),
                'tab'   => Controls_Manager::TAB_CONTENT,
            )
        );

        // Selector de servicio
        $services = $this->get_services_list();

        $this->add_control(
            'service_id',
            array(
                'label'       => __( 'Service', 'professional-booking-system' ),
                'type'        => Controls_Manager::SELECT,
                'options'     => $services,
                'default'     => ! empty( $services ) ? key( $services ) : '',
                'description' => __( 'Select the service for this booking widget', 'professional-booking-system' ),
            )
        );

        $this->add_control(
            'show_service_info',
            array(
                'label'        => __( 'Show Service Info', 'professional-booking-system' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Yes', 'professional-booking-system' ),
                'label_off'    => __( 'No', 'professional-booking-system' ),
                'return_value' => 'yes',
                'default'      => 'yes',
            )
        );

        $this->end_controls_section();

        // ========== ESTILOS: CONTENEDOR ==========
        $this->start_controls_section(
            'style_container',
            array(
                'label' => __( 'Container', 'professional-booking-system' ),
                'tab'   => Controls_Manager::TAB_STYLE,
            )
        );

        $this->add_control(
            'container_bg',
            array(
                'label'     => __( 'Background Color', 'professional-booking-system' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#ffffff',
                'selectors' => array(
                    '{{WRAPPER}} .pbs-booking-widget' => 'background-color: {{VALUE}};',
                ),
            )
        );

        $this->add_responsive_control(
            'container_padding',
            array(
                'label'      => __( 'Padding', 'professional-booking-system' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => array( 'px', 'em', '%' ),
                'default'    => array(
                    'top'    => '30',
                    'right'  => '30',
                    'bottom' => '30',
                    'left'   => '30',
                    'unit'   => 'px',
                ),
                'selectors'  => array(
                    '{{WRAPPER}} .pbs-booking-widget' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            array(
                'name'     => 'container_border',
                'selector' => '{{WRAPPER}} .pbs-booking-widget',
            )
        );

        $this->add_responsive_control(
            'container_border_radius',
            array(
                'label'      => __( 'Border Radius', 'professional-booking-system' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => array( 'px', '%' ),
                'default'    => array(
                    'top'    => '8',
                    'right'  => '8',
                    'bottom' => '8',
                    'left'   => '8',
                    'unit'   => 'px',
                ),
                'selectors'  => array(
                    '{{WRAPPER}} .pbs-booking-widget' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            array(
                'name'     => 'container_box_shadow',
                'selector' => '{{WRAPPER}} .pbs-booking-widget',
            )
        );

        $this->end_controls_section();

        // ========== ESTILOS: ENCABEZADO (INFO SERVICIO) ==========
        $this->start_controls_section(
            'style_header',
            array(
                'label' => __( 'Service Header', 'professional-booking-system' ),
                'tab'   => Controls_Manager::TAB_STYLE,
            )
        );

        $this->add_control(
            'header_bg',
            array(
                'label'     => __( 'Background Color', 'professional-booking-system' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#f8f9fa',
                'selectors' => array(
                    '{{WRAPPER}} .pbs-service-info' => 'background-color: {{VALUE}};',
                ),
            )
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            array(
                'name'     => 'header_title_typography',
                'label'    => __( 'Title Typography', 'professional-booking-system' ),
                'selector' => '{{WRAPPER}} .pbs-service-info h3',
            )
        );

        $this->add_control(
            'header_title_color',
            array(
                'label'     => __( 'Title Color', 'professional-booking-system' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#333333',
                'selectors' => array(
                    '{{WRAPPER}} .pbs-service-info h3' => 'color: {{VALUE}};',
                ),
            )
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            array(
                'name'     => 'header_text_typography',
                'label'    => __( 'Text Typography', 'professional-booking-system' ),
                'selector' => '{{WRAPPER}} .pbs-service-info p',
            )
        );

        $this->add_control(
            'header_text_color',
            array(
                'label'     => __( 'Text Color', 'professional-booking-system' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#666666',
                'selectors' => array(
                    '{{WRAPPER}} .pbs-service-info p' => 'color: {{VALUE}};',
                ),
            )
        );

        $this->add_responsive_control(
            'header_padding',
            array(
                'label'      => __( 'Padding', 'professional-booking-system' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => array( 'px', 'em' ),
                'default'    => array(
                    'top'    => '20',
                    'right'  => '20',
                    'bottom' => '20',
                    'left'   => '20',
                    'unit'   => 'px',
                ),
                'selectors'  => array(
                    '{{WRAPPER}} .pbs-service-info' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );

        $this->end_controls_section();

        // ========== ESTILOS: CALENDARIO ==========
        $this->start_controls_section(
            'style_calendar',
            array(
                'label' => __( 'Calendar', 'professional-booking-system' ),
                'tab'   => Controls_Manager::TAB_STYLE,
            )
        );

        $this->add_control(
            'calendar_primary_color',
            array(
                'label'     => __( 'Primary Color', 'professional-booking-system' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#0073aa',
                'selectors' => array(
                    '{{WRAPPER}} .pbs-calendar-header' => 'background-color: {{VALUE}};',
                    '{{WRAPPER}} .pbs-time-slot.selected' => 'background-color: {{VALUE}}; border-color: {{VALUE}};',
                ),
            )
        );

        $this->add_control(
            'calendar_day_bg',
            array(
                'label'     => __( 'Day Background', 'professional-booking-system' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#f0f0f0',
                'selectors' => array(
                    '{{WRAPPER}} .pbs-calendar-day' => 'background-color: {{VALUE}};',
                ),
            )
        );

        $this->add_control(
            'calendar_day_hover_bg',
            array(
                'label'     => __( 'Day Hover Background', 'professional-booking-system' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#0073aa',
                'selectors' => array(
                    '{{WRAPPER}} .pbs-calendar-day:hover' => 'background-color: {{VALUE}};',
                ),
            )
        );

        $this->add_control(
            'calendar_day_text_color',
            array(
                'label'     => __( 'Day Text Color', 'professional-booking-system' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#333333',
                'selectors' => array(
                    '{{WRAPPER}} .pbs-calendar-day' => 'color: {{VALUE}};',
                ),
            )
        );

        $this->end_controls_section();

        // ========== ESTILOS: SLOTS DE TIEMPO ==========
        $this->start_controls_section(
            'style_time_slots',
            array(
                'label' => __( 'Time Slots', 'professional-booking-system' ),
                'tab'   => Controls_Manager::TAB_STYLE,
            )
        );

        $this->add_control(
            'slot_bg',
            array(
                'label'     => __( 'Background Color', 'professional-booking-system' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#ffffff',
                'selectors' => array(
                    '{{WRAPPER}} .pbs-time-slot' => 'background-color: {{VALUE}};',
                ),
            )
        );

        $this->add_control(
            'slot_border_color',
            array(
                'label'     => __( 'Border Color', 'professional-booking-system' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#dddddd',
                'selectors' => array(
                    '{{WRAPPER}} .pbs-time-slot' => 'border-color: {{VALUE}};',
                ),
            )
        );

        $this->add_control(
            'slot_hover_bg',
            array(
                'label'     => __( 'Hover Background', 'professional-booking-system' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#e8f4f8',
                'selectors' => array(
                    '{{WRAPPER}} .pbs-time-slot:hover' => 'background-color: {{VALUE}};',
                ),
            )
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            array(
                'name'     => 'slot_typography',
                'selector' => '{{WRAPPER}} .pbs-time-slot',
            )
        );

        $this->end_controls_section();

        // ========== ESTILOS: FORMULARIO ==========
        $this->start_controls_section(
            'style_form',
            array(
                'label' => __( 'Form', 'professional-booking-system' ),
                'tab'   => Controls_Manager::TAB_STYLE,
            )
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            array(
                'name'     => 'form_label_typography',
                'label'    => __( 'Label Typography', 'professional-booking-system' ),
                'selector' => '{{WRAPPER}} .pbs-form-group label',
            )
        );

        $this->add_control(
            'form_label_color',
            array(
                'label'     => __( 'Label Color', 'professional-booking-system' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#333333',
                'selectors' => array(
                    '{{WRAPPER}} .pbs-form-group label' => 'color: {{VALUE}};',
                ),
            )
        );

        $this->add_control(
            'form_input_bg',
            array(
                'label'     => __( 'Input Background', 'professional-booking-system' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#ffffff',
                'selectors' => array(
                    '{{WRAPPER}} .pbs-form-group input, {{WRAPPER}} .pbs-form-group textarea' => 'background-color: {{VALUE}};',
                ),
            )
        );

        $this->add_control(
            'form_input_border_color',
            array(
                'label'     => __( 'Input Border Color', 'professional-booking-system' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#dddddd',
                'selectors' => array(
                    '{{WRAPPER}} .pbs-form-group input, {{WRAPPER}} .pbs-form-group textarea' => 'border-color: {{VALUE}};',
                ),
            )
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            array(
                'name'     => 'form_input_typography',
                'label'    => __( 'Input Typography', 'professional-booking-system' ),
                'selector' => '{{WRAPPER}} .pbs-form-group input, {{WRAPPER}} .pbs-form-group textarea',
            )
        );

        $this->end_controls_section();

        // ========== ESTILOS: BOTÓN ==========
        $this->start_controls_section(
            'style_button',
            array(
                'label' => __( 'Button', 'professional-booking-system' ),
                'tab'   => Controls_Manager::TAB_STYLE,
            )
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            array(
                'name'     => 'button_typography',
                'selector' => '{{WRAPPER}} .pbs-submit-btn',
            )
        );

        $this->add_control(
            'button_bg',
            array(
                'label'     => __( 'Background Color', 'professional-booking-system' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#0073aa',
                'selectors' => array(
                    '{{WRAPPER}} .pbs-submit-btn' => 'background-color: {{VALUE}};',
                ),
            )
        );

        $this->add_control(
            'button_text_color',
            array(
                'label'     => __( 'Text Color', 'professional-booking-system' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#ffffff',
                'selectors' => array(
                    '{{WRAPPER}} .pbs-submit-btn' => 'color: {{VALUE}};',
                ),
            )
        );

        $this->add_control(
            'button_hover_bg',
            array(
                'label'     => __( 'Hover Background', 'professional-booking-system' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#005a87',
                'selectors' => array(
                    '{{WRAPPER}} .pbs-submit-btn:hover' => 'background-color: {{VALUE}};',
                ),
            )
        );

        $this->add_responsive_control(
            'button_padding',
            array(
                'label'      => __( 'Padding', 'professional-booking-system' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => array( 'px', 'em' ),
                'default'    => array(
                    'top'    => '12',
                    'right'  => '30',
                    'bottom' => '12',
                    'left'   => '30',
                    'unit'   => 'px',
                ),
                'selectors'  => array(
                    '{{WRAPPER}} .pbs-submit-btn' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );

        $this->add_responsive_control(
            'button_border_radius',
            array(
                'label'      => __( 'Border Radius', 'professional-booking-system' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => array( 'px', '%' ),
                'range'      => array(
                    'px' => array( 'min' => 0, 'max' => 50 ),
                ),
                'default'    => array( 'size' => 4, 'unit' => 'px' ),
                'selectors'  => array(
                    '{{WRAPPER}} .pbs-submit-btn' => 'border-radius: {{SIZE}}{{UNIT}};',
                ),
            )
        );

        $this->end_controls_section();
    }

    /**
     * Render widget output on the frontend
     */
    protected function render() {
        $settings   = $this->get_settings_for_display();
        $service_id = ! empty( $settings['service_id'] ) ? intval( $settings['service_id'] ) : 0;

        if ( ! $service_id ) {
            echo '<p>' . __( 'Please select a service in widget settings.', 'professional-booking-system' ) . '</p>';
            return;
        }

        // Encolar el script
        wp_enqueue_script( 'pbs-booking-widget' );

        ?>
        <div class="pbs-booking-widget" data-service-id="<?php echo esc_attr( $service_id ); ?>">

            <?php if ( $settings['show_service_info'] === 'yes' ) : ?>
                <div class="pbs-service-info">
                    <div class="pbs-service-loading"><?php _e( 'Loading service info...', 'professional-booking-system' ); ?></div>
                </div>
            <?php endif; ?>

            <div class="pbs-calendar-section">
                <h4><?php _e( 'Select Date', 'professional-booking-system' ); ?></h4>
                <div class="pbs-calendar">
                    <div class="pbs-calendar-header">
                        <button class="pbs-calendar-prev">&laquo;</button>
                        <span class="pbs-calendar-month"></span>
                        <button class="pbs-calendar-next">&raquo;</button>
                    </div>
                    <div class="pbs-calendar-weekdays">
                        <div><?php _e( 'Mon', 'professional-booking-system' ); ?></div>
                        <div><?php _e( 'Tue', 'professional-booking-system' ); ?></div>
                        <div><?php _e( 'Wed', 'professional-booking-system' ); ?></div>
                        <div><?php _e( 'Thu', 'professional-booking-system' ); ?></div>
                        <div><?php _e( 'Fri', 'professional-booking-system' ); ?></div>
                        <div><?php _e( 'Sat', 'professional-booking-system' ); ?></div>
                        <div><?php _e( 'Sun', 'professional-booking-system' ); ?></div>
                    </div>
                    <div class="pbs-calendar-days"></div>
                </div>
            </div>

            <div class="pbs-time-section" style="display:none;">
                <h4><?php _e( 'Select Time', 'professional-booking-system' ); ?></h4>
                <div class="pbs-time-slots"></div>
            </div>

            <div class="pbs-form-section" style="display:none;">
                <h4><?php _e( 'Your Information', 'professional-booking-system' ); ?></h4>
                <form class="pbs-booking-form">
                    <div class="pbs-form-group">
                        <label><?php _e( 'Name', 'professional-booking-system' ); ?> *</label>
                        <input type="text" name="name" required>
                    </div>
                    <div class="pbs-form-group">
                        <label><?php _e( 'Email', 'professional-booking-system' ); ?> *</label>
                        <input type="email" name="email" required>
                    </div>
                    <div class="pbs-form-group">
                        <label><?php _e( 'Phone', 'professional-booking-system' ); ?></label>
                        <input type="tel" name="phone">
                    </div>
                    <div class="pbs-form-group">
                        <label><?php _e( 'Notes', 'professional-booking-system' ); ?></label>
                        <textarea name="notes" rows="3"></textarea>
                    </div>
                    <button type="submit" class="pbs-submit-btn"><?php _e( 'Confirm Booking', 'professional-booking-system' ); ?></button>
                </form>
                <div class="pbs-message" style="display:none;"></div>
            </div>

        </div>
        <?php
    }

    /**
     * Get list of services for dropdown
     */
    private function get_services_list() {
        $services_obj = PBS_Services::get_instance()->get_all_services( true );
        $options      = array();

        if ( ! empty( $services_obj ) ) {
            foreach ( $services_obj as $service ) {
                $options[ $service['id'] ] = $service['name'];
            }
        } else {
            $options[''] = __( 'No services available', 'professional-booking-system' );
        }

        return $options;
    }
}