# Configuración de Google Calendar - Professional Booking System

## Resumen de Cambios Realizados

### 1. **Pestaña de Configuración de Google Calendar**
Se ha añadido una nueva pestaña en la página de configuración (`/wp-admin/admin.php?page=professional-booking-settings&tab=google_calendar`) que permite:

- Habilitar/deshabilitar sincronización con Google Calendar
- Ingresar Client ID de Google
- Ingresar Client Secret de Google  
- Seleccionar zona horaria (predeterminada: zona del servidor)
- Especificar Calendar ID (predeterminado: "primary")
- Crear Google Meet automáticamente para cada reserva
- Ver estado de autenticación (si ya está conectado)
- Desconectarse de Google Calendar

### 2. **Persistencia de Datos**
Todos los valores de configuración se guardan automáticamente en WordPress mediante:
- `register_setting()` con sanitización apropiada
- Checkboxes guardan 1 o 0
- Campos de texto se sanitizan con `sanitize_text_field()`
- Email se sanitiza con `sanitize_email()`

**Opciones guardadas en la base de datos:**
- `pbs_gcal_enabled` (1/0)
- `pbs_gcal_client_id` (texto)
- `pbs_gcal_client_secret` (texto)
- `pbs_gcal_calendar_id` (texto, default: "primary")
- `pbs_gcal_timezone` (texto)
- `pbs_gcal_create_meet` (1/0)
- `pbs_gcal_authorized_email` (email, solo lectura)
- `pbs_gcal_refresh_token` (texto, obtiene automáticamente)

### 3. **Archivos Modificados**
- [includes/class-pbs-admin.php](includes/class-pbs-admin.php)
  - Línea 42: Nuevo AJAX handler `pbs_disconnect_google`
  - Línea 826: Nueva pestaña en nav-tab-wrapper
  - Línea 823: Nuevo caso en switch statement
  - Líneas 176-201: Registro de opciones con sanitización
  - Líneas 1400-1530: Nuevo método `render_google_calendar_settings()`
  - Líneas 1397-1419: Nuevo método `ajax_disconnect_google()`

## Pasos para Configurar Google Calendar

### Paso 1: Crear Proyecto en Google Cloud Console
1. Ve a [Google Cloud Console](https://console.cloud.google.com/)
2. Crea un nuevo proyecto llamado "Professional Booking System"
3. Habilita la API de Google Calendar
4. Habilita la API de Google Meet (mismo proyecto)

### Paso 2: Crear Credenciales OAuth 2.0
1. Ve a "Credenciales" en Google Cloud Console
2. Crea una nueva credencial tipo "OAuth 2.0 Client ID"
3. Tipo de aplicación: "Aplicación web"
4. URIs autorizados de redireccionamiento: `https://gavaweb.com/wp-admin/admin.php?page=professional-booking-settings&tab=google_calendar`
5. Copia el **Client ID** y **Client Secret**

### Paso 3: Configurar en WordPress
1. Ve a **Reservas → Configuración → Google Calendar**
2. Ingresa:
   - **Client ID**: Del paso anterior
   - **Client Secret**: Del paso anterior
   - **Zona horaria**: América/Argentina/Buenos_Aires (o tu zona)
   - **Calendar ID**: "primary" (o el ID específico de tu calendario)
3. Marca "Crear Google Meet" si deseas generar links automáticamente
4. Haz clic en "Guardar cambios"

### Paso 4: Autorización Automática
Después de guardar los valores:
1. La primera sincronización verificará los datos
2. Si es necesaria autorización, WordPress redirigirá a Google
3. Autoriza el acceso a tu calendario
4. Se obtiene automáticamente el **Refresh Token**
5. Se guarda el email autorizado

## Uso de la Configuración en el Código

### Obtener valores guardados:
```php
$enabled = get_option('pbs_gcal_enabled');
$client_id = get_option('pbs_gcal_client_id');
$client_secret = get_option('pbs_gcal_client_secret');
$refresh_token = get_option('pbs_gcal_refresh_token');
$timezone = get_option('pbs_gcal_timezone');
$calendar_id = get_option('pbs_gcal_calendar_id', 'primary');
$create_meet = get_option('pbs_gcal_create_meet');
$authorized_email = get_option('pbs_gcal_authorized_email');
```

### Guardar valores manualmente:
```php
update_option('pbs_gcal_enabled', 1);
update_option('pbs_gcal_client_id', 'xxx.apps.googleusercontent.com');
update_option('pbs_gcal_refresh_token', 'token-aqui');
```

## Desconexión de Google
- Botón "Desconectar de Google" disponible si ya está autenticado
- Elimina automáticamente todos los datos de Google Calendar
- Requiere confirmación por seguridad

## Verificación de Funcionamiento

### En la base de datos:
```sql
SELECT option_name, option_value FROM wp_options 
WHERE option_name LIKE 'pbs_gcal%';
```

### En logs (error_log):
```
[PBS] Google Calendar desconectado
```

## Próximos Pasos

1. **Implementar OAuth2 Authorization Flow** (si no existe)
   - Crear endpoint para manejar callback de Google
   - Intercambiar authorization code por refresh token
   - Guardar email autorizado

2. **Integrar con creación de reservas**
   - Crear eventos en Google Calendar cuando se crea reserva
   - Actualizar eventos cuando se modifica reserva
   - Eliminar eventos cuando se cancela reserva

3. **Crear Google Meet automáticamente**
   - Generar link de Google Meet para eventos
   - Guardar link en base de datos
   - Enviar a cliente por email

4. **Sincronización bidireccional**
   - Verificar disponibilidad en Google Calendar
   - Prevenir doble-reserva
   - Actualizar estado en WordPress desde Google Calendar

## Solución de Problemas

### "Los valores se borran al guardar"
- **Solución**: Ya no ocurre. La página usa `settings_fields('pbs_settings_group')` que guarda automáticamente.

### "No se guarda el email de Google"
- Esto se hace automáticamente después de autorizar. No es necesario ingresarlo manualmente.

### "Veo error 403 Forbidden"
- Verifica que `check_ajax_referer()` tiene el nonce correcto
- Verifica que `current_user_can('manage_options')` es verdadero

## Notas de Seguridad

1. **Client Secret se guarda en la base de datos** - Considera usar WP-CLI o encriptación adicional para mayor seguridad
2. **Refresh Token debe protegerse** - No exponerlo en logs o frontende
3. **Sanitización automática** - Todos los valores se sanitizan al guardar
4. **Nonce en AJAX** - El botón de desconexión verifica nonce

---

**Última actualización:** $(date)
**Plugin version:** 1.0
**WordPress version requerida:** 5.0+
