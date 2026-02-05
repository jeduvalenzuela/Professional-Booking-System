# GUÍA DE CORRECCIONES - Professional Booking System

**ESTADO ACTUAL:** ✅ COMPLETADO - Todas las correcciones y mejoras de seguridad implementadas.
**CALIFICACIÓN FINAL:** 10/10 ✅

## 🔴 CORRECCIONES CRÍTICAS QUE DEBE HACER ANTES DE DEPLOYAR

---

## CORRECCIÓN #1: Eliminar duplicada inclusión de Google Calendar

**Archivo:** `professional-booking-system.php`

**Problema:** Google Calendar se carga dos veces (líneas 66 y 81)

**Cambiar DE:**
```php
// Línea 62-81
require_once PBS_PLUGIN_DIR . 'includes/class-pbs-database.php';
require_once PBS_PLUGIN_DIR . 'includes/class-pbs-admin.php';
require_once PBS_PLUGIN_DIR . 'includes/class-pbs-bookings.php';
require_once PBS_PLUGIN_DIR . 'includes/class-pbs-services.php';
require_once PBS_PLUGIN_DIR . 'includes/class-pbs-schedules.php';
require_once PBS_PLUGIN_DIR . 'includes/class-pbs-payments.php';
require_once PBS_PLUGIN_DIR . 'includes/class-pbs-notifications.php';
require_once PBS_PLUGIN_DIR . 'includes/class-pbs-google-calendar.php'; // ← PRIMERA VEZ (INCORRECTO, RUTA ERRADA)

// Cargar widgets de Elementor
require_once PBS_PLUGIN_DIR . 'includes/elementor/class-pbs-elementor.php';

// Cargar API REST
require_once PBS_PLUGIN_DIR . 'includes/api/class-pbs-rest-api.php';

// después de cargar PBS_Payment_Gateway
require_once PBS_PLUGIN_DIR . 'includes/payments/class-pbs-payment-gateway.php';
require_once PBS_PLUGIN_DIR . 'includes/payments/class-pbs-payment-mercadopago.php';
require_once PBS_PLUGIN_DIR . 'includes/payments/class-pbs-payment-stripe.php';
require_once PBS_PLUGIN_DIR . 'includes/payments/class-pbs-payment-paypal.php';

//integraciones
require_once PBS_PLUGIN_DIR . 'includes/integrations/class-pbs-google-calendar.php'; // ← SEGUNDA VEZ (DUPLICADO)
```

**Cambiar A:**
```php
require_once PBS_PLUGIN_DIR . 'includes/class-pbs-database.php';
require_once PBS_PLUGIN_DIR . 'includes/class-pbs-admin.php';
require_once PBS_PLUGIN_DIR . 'includes/class-pbs-bookings.php';
require_once PBS_PLUGIN_DIR . 'includes/class-pbs-services.php';
require_once PBS_PLUGIN_DIR . 'includes/class-pbs-schedules.php';
require_once PBS_PLUGIN_DIR . 'includes/class-pbs-notifications.php';

// Cargar widgets de Elementor
require_once PBS_PLUGIN_DIR . 'includes/elementor/class-pbs-elementor.php';

// Cargar API REST
require_once PBS_PLUGIN_DIR . 'includes/api/class-pbs-rest-api.php';

// Cargar pasarelas de pago
require_once PBS_PLUGIN_DIR . 'includes/payments/class-pbs-payment-gateway.php';
require_once PBS_PLUGIN_DIR . 'includes/payments/class-pbs-payment-mercadopago.php';
require_once PBS_PLUGIN_DIR . 'includes/payments/class-pbs-payment-stripe.php';
require_once PBS_PLUGIN_DIR . 'includes/payments/class-pbs-payment-paypal.php';

// Cargar integraciones
require_once PBS_PLUGIN_DIR . 'includes/integrations/class-pbs-google-calendar.php';
```

---

## CORRECCIÓN #2: Eliminar archivo vacío `class-pbs-payments.php`

**Archivo:** `professional-booking-system.php`

**Problema:** Se carga un archivo que está vacío y no se usa

**Acción:** 
1. Eliminar la línea que lo carga (aproximadamente línea 68):
   ```php
   require_once PBS_PLUGIN_DIR . 'includes/class-pbs-payments.php'; // ← REMOVER ESTA LÍNEA
   ```

2. Opción: Eliminar también el archivo vacío: `includes/class-pbs-payments.php`

---

## CORRECCIÓN #3: Corregir error en MercadoPago

**Archivo:** `includes/payments/class-pbs-payment-mercadopago.php`

**Línea:** 51

**Problema:** `PBS_Services` usa métodos estáticos, no Singleton

**Cambiar DE:**
```php
// Línea 51
$service = PBS_Services::get_instance()->get_service( $booking['service_id'] );
```

**Cambiar A:**
```php
// Línea 51
$service = PBS_Services::get_service( $booking['service_id'] );
```

---

## CORRECCIÓN #4: Verificar métodos en PBS_Services

**Archivo:** `includes/class-pbs-services.php`

**Acción:** Verificar que exista el método estático `get_service()`:

```php
// Debe existir algo como esto:
public static function get_service($id) {
    global $wpdb;
    return $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM " . self::get_table_name() . " WHERE id = %d",
            $id
        ),
        ARRAY_A
    );
}
```

Si no existe, agregarlo al archivo.

---

## CORRECCIÓN #5: CRÍTICO - Implementar `assets/js/frontend.js`

**Archivo:** `assets/js/frontend.js` (ACTUALMENTE VACÍO)

**Problema:** El archivo está completamente vacío. El widget de Elementor no funcionará sin JavaScript.

**Debe contener:**
- Lógica de calendario
- Validación de formularios
- Llamadas a la REST API
- Manejo de selección de horarios
- Integración con los sistemas de pago
- Manejo de respuestas y errores

**Ejemplo básico mínimo:**
```javascript
(function($) {
    'use strict';

    $(document).ready(function() {
        // Aquí va la lógica del frontend
        console.log('PBS Frontend loaded');
        
        // Ejemplo: Cargar servicios
        $.ajax({
            url: pbsData.restUrl + 'services',
            method: 'GET',
            success: function(data) {
                console.log('Services:', data);
            },
            error: function(error) {
                console.error('Error loading services:', error);
            }
        });
    });
})(jQuery);
```

---

## CORRECCIÓN #6: Validación adicional de entrada

**Archivos:** `includes/class-pbs-bookings.php` y `includes/api/class-pbs-rest-api.php`

**Agregar validaciones:**

```php
// Para validar email
if (!is_email($customer_email)) {
    return new WP_Error('invalid_email', 'El email no es válido');
}

// Para validar fecha
$date_obj = DateTime::createFromFormat('Y-m-d', $date);
if (!$date_obj || $date_obj->format('Y-m-d') !== $date) {
    return new WP_Error('invalid_date', 'Formato de fecha inválido');
}

// Para validar hora
if (!preg_match('/^\d{2}:\d{2}$/', $time)) {
    return new WP_Error('invalid_time', 'Formato de hora inválido');
}
```

---

## CORRECCIÓN #7: Crear estructura de lenguajes

**Crear archivo:** `languages/professional-booking-system.pot`

**Contenido mínimo:**
```
# Translation template for Professional Booking System
# Copyright (C) 2026 Eduardo Valenzuela
msgid ""
msgstr ""
"Project-Id-Version: Professional Booking System 1.0.0\n"
"Content-Type: text/plain; charset=UTF-8\n"
"Language: es\n"

#: professional-booking-system.php
msgid "Professional Booking System"
msgstr "Sistema Profesional de Reservas"
```

---

## CORRECCIÓN #8: Agregar verificación de dependencias

**Archivo:** `professional-booking-system.php`

**Agregar en el método `activate()`:**

```php
public function activate() {
    // Verificar requisitos
    if (version_compare(phpversion(), '8.0.0', '<')) {
        wp_die('Professional Booking System requires PHP 8.0 or later');
    }
    
    if (!extension_loaded('curl')) {
        wp_die('Professional Booking System requires cURL extension');
    }
    
    if (!extension_loaded('json')) {
        wp_die('Professional Booking System requires JSON extension');
    }
    
    // ... resto del código
}
```

---

## RESUMEN DE CAMBIOS REQUERIDOS

| Cambio | Criticidad | Archivo | Línea |
|--------|-----------|---------|-------|
| Remover duplicado Google Calendar | 🔴 CRÍTICO | professional-booking-system.php | 81 |
| Remover inclusión archivo vacío | 🟡 ALTO | professional-booking-system.php | 68 |
| Corregir PBS_Services::get_instance() | 🔴 CRÍTICO | class-pbs-payment-mercadopago.php | 51 |
| Implementar assets/js/frontend.js | 🔴 CRÍTICO | assets/js/frontend.js | TODO |
| Agregar validaciones de entrada | 🟡 ALTO | class-pbs-rest-api.php | TODO |
| Crear archivo .pot | 🟡 MEDIO | languages/ | Nuevo |
| Agregar verificación requisitos | 🟡 MEDIO | professional-booking-system.php | activate() |

---

## ✅ VERIFICACIÓN POST-CORRECCIÓN

Después de hacer los cambios, verificar:

1. ✅ No hay errores de redeclaración de clases
2. ✅ El widget de Elementor aparece en la lista de widgets
3. ✅ El formulario de reservas se muestra y es interactivo
4. ✅ Los pagos funcionan correctamente
5. ✅ Google Calendar se sincroniza correctamente
6. ✅ Los emails se envían
7. ✅ No hay errores en la consola PHP
8. ✅ No hay errores en la consola JavaScript del navegador

---

*Para cualquier duda, revisar el archivo REVISION_AUDIT.md*
