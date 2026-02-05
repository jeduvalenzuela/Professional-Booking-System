<?php
/**
 * Tests unitarios para Professional Booking System
 *
 * @package Professional_Booking_System
 */

if (!defined('ABSPATH')) {
    exit;
}

class PBS_Tests {

    /**
     * Tests para PBS_Services
     */
    public static function test_services(): array {
        $results = array();

        // Test 1: Crear servicio
        $service_data = array(
            'name' => 'Consulta Médica',
            'description' => 'Consulta médica general',
            'duration' => 60,
            'price' => 100.00,
            'status' => 'active',
        );

        $service_id = PBS_Services::create($service_data);
        $results['create_service'] = array(
            'passed' => is_int($service_id) && $service_id > 0,
            'message' => is_int($service_id) ? "Servicio creado con ID: $service_id" : 'Error al crear servicio',
        );

        // Test 2: Obtener servicio
        if (is_int($service_id)) {
            $service = PBS_Services::get($service_id);
            $results['get_service'] = array(
                'passed' => $service && $service['name'] === 'Consulta Médica',
                'message' => $service ? "Servicio obtenido correctamente" : 'Error al obtener servicio',
            );

            // Test 3: Actualizar servicio
            $updated = PBS_Services::update($service_id, array('price' => 150.00));
            $results['update_service'] = array(
                'passed' => $updated === true,
                'message' => $updated === true ? 'Servicio actualizado' : 'Error al actualizar',
            );

            // Test 4: Verificar que está activo
            $is_active = PBS_Services::is_active($service_id);
            $results['is_active'] = array(
                'passed' => $is_active === true,
                'message' => $is_active ? 'Servicio marcado como activo' : 'Servicio no activo',
            );

            // Test 5: Listar servicios
            $services = PBS_Services::get_all(array('status' => 'active'));
            $results['get_all'] = array(
                'passed' => is_array($services) && count($services) > 0,
                'message' => 'Se obtuvieron ' . count($services) . ' servicios',
            );

            // Test 6: Eliminar servicio
            $deleted = PBS_Services::delete($service_id);
            $results['delete_service'] = array(
                'passed' => $deleted === true,
                'message' => $deleted === true ? 'Servicio eliminado' : 'Error al eliminar',
            );
        }

        return $results;
    }

    /**
     * Tests para PBS_Bookings
     */
    public static function test_bookings(): array {
        $results = array();

        // Crear servicio primero
        $service_id = PBS_Services::create(array(
            'name' => 'Test Service',
            'duration' => 60,
            'price' => 100.00,
        ));

        if (is_int($service_id)) {
            // Test 1: Crear reserva
            $booking_data = array(
                'service_id' => $service_id,
                'customer_name' => 'Juan Pérez',
                'customer_email' => 'juan@example.com',
                'booking_date' => date('Y-m-d', strtotime('+1 day')),
                'booking_time' => '10:00',
                'payment_amount' => 100.00,
            );

            $booking = PBS_Bookings::create_booking($booking_data);
            $results['create_booking'] = array(
                'passed' => is_array($booking) && isset($booking['id']),
                'message' => is_array($booking) ? "Reserva creada con ID: {$booking['id']}" : 'Error al crear reserva',
            );

            if (is_array($booking)) {
                $booking_id = $booking['id'];

                // Test 2: Obtener reserva
                $retrieved = PBS_Bookings::get($booking_id);
                $results['get_booking'] = array(
                    'passed' => $retrieved && $retrieved['customer_name'] === 'Juan Pérez',
                    'message' => $retrieved ? 'Reserva obtenida correctamente' : 'Error al obtener',
                );

                // Test 3: Actualizar estado
                $updated = PBS_Bookings::update_status($booking_id, 'confirmed');
                $results['update_status'] = array(
                    'passed' => $updated === true,
                    'message' => $updated === true ? 'Estado actualizado' : 'Error al actualizar',
                );

                // Test 4: Actualizar estado de pago
                $paid = PBS_Bookings::update_payment_status($booking_id, 'paid', 'TRX123456');
                $results['update_payment'] = array(
                    'passed' => $paid === true,
                    'message' => $paid === true ? 'Pago registrado' : 'Error en pago',
                );

                // Test 5: Verificar que el slot está tomado
                $is_taken = PBS_Bookings::is_slot_taken($service_id, $booking_data['booking_date'], $booking_data['booking_time']);
                $results['is_slot_taken'] = array(
                    'passed' => $is_taken === true,
                    'message' => 'Slot verificado como tomado',
                );
            }
        }

        return $results;
    }

    /**
     * Tests para PBS_Security
     */
    public static function test_security(): array {
        $results = array();
        $security = PBS_Security::get_instance();

        // Test 1: CSRF Token
        $token = $security->get_csrf_token();
        $results['csrf_token'] = array(
            'passed' => !empty($token) && strlen($token) > 20,
            'message' => 'Token CSRF generado',
        );

        // Test 2: Verificar CSRF Token
        $verified = $security->verify_csrf_token($token);
        $results['verify_csrf'] = array(
            'passed' => $verified === true,
            'message' => 'Token CSRF verificado',
        );

        // Test 3: Rate Limiting
        $endpoint = 'test_endpoint';
        $allowed = $security->check_rate_limit($endpoint, 5, 60);
        $results['rate_limit_allow'] = array(
            'passed' => $allowed === true,
            'message' => 'Rate limit permitido',
        );

        // Test 4: Exceder rate limit
        for ($i = 0; $i < 5; $i++) {
            $security->check_rate_limit($endpoint, 5, 60);
        }
        $exceeded = $security->check_rate_limit($endpoint, 5, 60);
        $results['rate_limit_exceed'] = array(
            'passed' => $exceeded === false,
            'message' => 'Rate limit excedido correctamente',
        );

        // Test 5: Log de auditoría
        $logged = $security->log_audit(
            'test_action',
            'test_object',
            1,
            array('old' => 'value'),
            array('new' => 'value')
        );
        $results['audit_log'] = array(
            'passed' => $logged === true,
            'message' => 'Evento registrado en auditoría',
        );

        return $results;
    }

    /**
     * Ejecutar todos los tests
     */
    public static function run_all_tests(): array {
        return array(
            'services' => self::test_services(),
            'bookings' => self::test_bookings(),
            'security' => self::test_security(),
        );
    }

    /**
     * Generar reporte de tests
     */
    public static function generate_report(): string {
        $tests = self::run_all_tests();
        $report = "# REPORTE DE TESTS - Professional Booking System\n\n";
        $report .= "Generado: " . current_time('mysql') . "\n\n";

        $total_tests = 0;
        $passed_tests = 0;

        foreach ($tests as $category => $category_tests) {
            $report .= "## " . ucfirst($category) . "\n\n";

            foreach ($category_tests as $test_name => $result) {
                $total_tests++;
                $status = $result['passed'] ? '✅ PASÓ' : '❌ FALLÓ';
                if ($result['passed']) {
                    $passed_tests++;
                }
                $report .= "- **$test_name**: $status - {$result['message']}\n";
            }

            $report .= "\n";
        }

        $percentage = round(($passed_tests / $total_tests) * 100, 1);
        $report .= "---\n\n";
        $report .= "**Resumen**: $passed_tests/$total_tests tests pasaron ($percentage%)\n";

        return $report;
    }
}

/**
 * Funciones helper para tests
 */
function pbs_run_tests(): void {
    $report = PBS_Tests::generate_report();
    echo wp_kses_post($report);
}

function pbs_get_test_results(): array {
    return PBS_Tests::run_all_tests();
}
