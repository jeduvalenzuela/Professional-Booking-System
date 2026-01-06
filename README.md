# Professional Booking System

Professional Booking System es un plugin de WordPress para gestionar reservas y pagos, con integración a Google Calendar y widgets para Elementor.

## Características

- Gestión de servicios, horarios y excepciones (días bloqueados).
- Reservas con bloqueo temporal durante pago.
- Integraciones de pago: MercadoPago, Stripe, PayPal.
- Integración con Google Calendar (opcional).
- Panel de administración con gestión de reservas, servicios y configuraciones.
- Endpoints REST públicos para consumir servicios y crear reservas desde el frontend.
- Widget para Elementor (incluido).

## Requisitos

- WordPress 6.0+
- PHP 8.0+
- Extensiones PHP necesarias: cURL (para llamadas a APIs externas) y JSON

## Instalación

1. Copia la carpeta del plugin al directorio `wp-content/plugins/professional-booking-system`.
2. Activa el plugin desde el panel de administración de WordPress (Plugins).
3. En la activación el plugin crea las tablas necesarias en la base de datos.

## Configuración básica

Accede a `Reservas -> Configuración` en el menú de administración para configurar:

- Nombre del profesional y zona horaria.
- Proveedores de pago y claves (Stripe, MercadoPago, PayPal).
- Habilitar Google Calendar y proporcionar credenciales (Client ID / Secret / Refresh Token).
- Plantillas y ajustes de notificaciones por email.

Archivos clave:
- `professional-booking-system.php` (bootstrap del plugin)
- `includes/class-pbs-admin.php` (panel admin y vistas)
- `includes/api/class-pbs-rest-api.php` (rutas REST)
- `includes/class-pbs-bookings.php` (lógica de reservas)
- `includes/class-pbs-database.php` (creación de tablas)
- `includes/payments/` (integraciones de pago)
- `includes/integrations/class-pbs-google-calendar.php` (Google Calendar)

## Uso en frontend

- Widget de Elementor: busca el widget relacionado con "Professional Booking" en la lista de widgets de Elementor e insértalo en tu página.
- El plugin expone endpoints REST que el JavaScript del frontend consume para:
  - Listar servicios: `GET /wp-json/professional-booking-system/v1/services`
  - Obtener servicio: `GET /wp-json/professional-booking-system/v1/services/{id}`
  - Disponibilidad día: `GET /wp-json/professional-booking-system/v1/availability/day?service_id=ID&date=YYYY-MM-DD`
  - Crear reserva: `POST /wp-json/professional-booking-system/v1/bookings/create` (JSON)

Endpoints de pagos:
  - MercadoPago: `POST /wp-json/professional-booking-system/v1/payments/mercadopago/create_preference`
  - MercadoPago webhook: `POST /wp-json/professional-booking-system/v1/payments/mercadopago/webhook`
  - Stripe create session: `POST /wp-json/professional-booking-system/v1/payments/stripe/create_session`
  - Stripe webhook: `POST /wp-json/professional-booking-system/v1/payments/stripe/webhook`
  - PayPal create order: `POST /wp-json/professional-booking-system/v1/payments/paypal/create_order`
  - PayPal webhook: `POST /wp-json/professional-booking-system/v1/payments/paypal/webhook`

Nota: la namespace REST es `professional-booking-system/v1`.

## Seguridad y buenas prácticas (importante)

- Webhooks: configura y verifica las firmas de los webhooks (Stripe webhook secret, MercadoPago signature/verification, PayPal verification). Actualmente el plugin necesita que se agregue la verificación de firma en los manejadores de webhook.
- Nonces y permisos: proteger endpoints sensibles (creación/actualización desde el admin) con nonces o `permission_callback` adecuados.
- Validación y sanitización: el plugin ya usa funciones WP (`sanitize_text_field`, `sanitize_email`, `wp_kses_post`, `$wpdb->prepare`), pero revisa las rutas REST públicas y AJAX para asegurar permisos y límites de tasa si expones la API públicamente.
- Almacenamiento de claves: usa las opciones de WordPress para las claves, pero asegúrate de que los archivos de backup o repositorios privados no incluyan claves en texto plano.

## Desarrollo y notas internas

- Las tablas que crea el plugin están en el prefijo `pbs_` (ej. `wp_pbs_bookings`, `wp_pbs_services`).
- Si necesitas reiniciar la DB de plugin, hay funciones en `PBS_Database::drop_tables()` (usar con precaución).
- Clase de servicios: `includes/class-pbs-services.php` — contiene `get_all()` y `get()` para obtener servicios.
- Clase de reservas: `includes/class-pbs-bookings.php` — contiene `create_booking()`, `is_slot_taken()`, `update_payment_status()`.
- Admin: `includes/class-pbs-admin.php` gestiona las vistas y AJAX del admin.

## Problemas conocidos y tareas recomendadas

- Corregir verificación de firmas en todos los webhooks (Stripe, MercadoPago, PayPal).
- Unificar convención de APIs internas: algunas clases usan métodos estáticos y otras singletons; revisar y normalizar.
- Asegurar `permission_callback` en rutas REST sensibles.
- Revisar duplicados en includes (Google Calendar) y limpiar `require_once` redundantes.

## Contribuir

Para contribuir, por favor abre un issue o pull request en el repositorio. Sigue las convenciones de código de WordPress (PHPCS/PSR cuando aplique).

## Licencia

GPL v2 o posterior.

## Changelog

- 1.0.0 — Versión inicial
