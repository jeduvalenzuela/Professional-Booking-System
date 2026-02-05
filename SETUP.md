# Guía de Configuración - Professional Booking System v1.0.0

## 📋 Índice
1. [Requisitos](#requisitos)
2. [Instalación](#instalación)
3. [Configuración Inicial](#configuración-inicial)
4. [Configuración de Servicios](#configuración-de-servicios)
5. [Configuración de Horarios](#configuración-de-horarios)
6. [Integración de Pagos](#integración-de-pagos)
7. [Google Calendar](#google-calendar)
8. [Notificaciones por Email](#notificaciones-por-email)
9. [Widget de Elementor](#widget-de-elementor)
10. [Troubleshooting](#troubleshooting)

---

## Requisitos

- **WordPress:** 6.0 o superior
- **PHP:** 8.0 o superior
- **MySQL:** 5.7 o superior
- **Extensiones PHP necesarias:** cURL, JSON
- **Elementor:** (Opcional, para usar el widget)

### Verificar requisitos

```bash
# PHP CLI
php -v
php -m | grep -E "curl|json"

# WordPress Admin
Herramientas → Estado del Sitio
```

---

## Instalación

### Paso 1: Descarga e instalación

```bash
# Opción A: Copiar manualmente
1. Descarga el plugin
2. Copia la carpeta 'professional-booking-system' a:
   wp-content/plugins/

# Opción B: Zip desde admin
1. Ve a: Plugins → Añadir nuevo
2. Sube el archivo ZIP
3. Haz clic en "Activar"
```

### Paso 2: Activación

```
WordPress Admin → Plugins → Busca "Professional Booking System" → Activar
```

### Paso 3: Verifica la activación

Después de activar, debería aparecer:
- ✅ Nuevo menú "Reservas" en el admin
- ✅ Nuevas tablas en la base de datos
- ✅ Datos de ejemplo (1 servicio, 10 horarios)

---

## Configuración Inicial

### 1. Acceder a Configuración

```
Menú Admin → Reservas → Configuración
```

### 2. Datos Generales

| Campo | Descripción | Ejemplo |
|-------|-------------|---------|
| **Nombre Profesional** | Tu nombre/empresa | "Dr. García" |
| **Especialidad** | Tu profesión/especialidad | "Médico" |
| **Zona Horaria** | Tu zona horaria | "America/Argentina/Buenos_Aires" |

### 3. Configuración de Divisas y Pagos

| Campo | Descripción | Opciones |
|-------|-------------|----------|
| **Moneda** | Moneda para pagos | USD, ARS, EUR, etc. |
| **Require Pago** | ¿Requerir pago? | - Full (100% al confirmar) - Deposit (% al confirmar) - No requerido |
| **Porcentaje Seña** | Si es depósito | 0-100 |

### 4. Guardar Cambios

Haz clic en "Guardar" al final

---

## Configuración de Servicios

### 1. Agregar Nuevo Servicio

```
Menú Admin → Reservas → Servicios → Agregar Nuevo
```

### 2. Completa los campos

| Campo | Descripción | Ejemplo |
|-------|-------------|---------|
| **Nombre** | Nombre del servicio | "Consulta General" |
| **Descripción** | Descripción detallada | "Consulta de 60 minutos" |
| **Duración (min)** | Duración en minutos | 60 |
| **Precio** | Precio del servicio | 100 |
| **Habilitar Videollamada** | ¿Permitir videollamada? | Sí/No |
| **Categoría** | Categoría del servicio | Consulta, Asesoría, etc. |

### 3. Guardar

Haz clic en "Guardar Servicio"

### 4. Editar o Eliminar

- **Editar:** Haz clic sobre el servicio
- **Eliminar:** Botón "Eliminar" al editar

---

## Configuración de Horarios

### 1. Acceder a Horarios

```
Menú Admin → Reservas → Horarios
```

### 2. Agregar Horario

Haz clic en "Agregar Horario"

### 3. Completa los campos

| Campo | Descripción | Ejemplo |
|-------|-------------|---------|
| **Día de la Semana** | Día (Lunes a Domingo) | Lunes |
| **Hora Inicio** | Hora de apertura | 09:00 |
| **Hora Fin** | Hora de cierre | 13:00 |
| **Activo** | ¿Está disponible? | ✓ |

### 4. Notas

- Puedes agregar múltiples franjas por día (ej: 9-13 y 15-19)
- Los horarios aplican a TODOS los servicios
- Para excepciones, ve a "Excepciones"

### 5. Ejemplo: Horario Típico

```
Lunes:    09:00 - 13:00 (descanso) 15:00 - 19:00
Martes:   09:00 - 13:00 (descanso) 15:00 - 19:00
Miércoles: 09:00 - 13:00 (descanso) 15:00 - 19:00
Jueves:   09:00 - 13:00 (descanso) 15:00 - 19:00
Viernes:  09:00 - 13:00 (descanso) 15:00 - 19:00
Sábado:   INACTIVO
Domingo:  INACTIVO
```

---

## Integración de Pagos

El plugin soporta: **MercadoPago**, **Stripe**, **PayPal**

### Configurar MercadoPago

#### 1. Obtener credenciales

```
1. Ve a: https://www.mercadopago.com/
2. Inicia sesión con tu cuenta
3. Ve a: Configuración → Integraciones → OAuth
4. Copia tu Access Token
```

#### 2. Agregar en WordPress

```
Menú Admin → Reservas → Configuración → Pagos → MercadoPago
```

| Campo | Valor |
|-------|-------|
| **Proveedor de Pago** | MercadoPago |
| **Access Token** | `Tu_token_aqui` |
| **Modo** | `sandbox` (prueba) o `production` |

#### 3. Guardar

### Configurar Stripe

#### 1. Obtener credenciales

```
1. Ve a: https://dashboard.stripe.com/
2. Inicia sesión
3. Ve a: Developers → API Keys
4. Copia Public Key y Secret Key
```

#### 2. Agregar en WordPress

```
Menú Admin → Reservas → Configuración → Pagos → Stripe
```

| Campo | Valor |
|-------|-------|
| **Proveedor de Pago** | Stripe |
| **Public Key** | `pk_test_xxxxx` |
| **Secret Key** | `sk_test_xxxxx` |
| **Modo** | `test` (prueba) o `live` |

### Configurar PayPal

#### 1. Obtener credenciales

```
1. Ve a: https://www.paypal.com/
2. Inicia sesión
3. Ve a: Herramientas → Aplicaciones
4. Copia Client ID y Secret
```

#### 2. Agregar en WordPress

```
Menú Admin → Reservas → Configuración → Pagos → PayPal
```

| Campo | Valor |
|-------|-------|
| **Proveedor de Pago** | PayPal |
| **Client ID** | `Tu_client_id` |
| **Secret** | `Tu_secret` |
| **Sandbox** | ✓ (para pruebas) |

---

## Google Calendar

### 1. Preparar Google Cloud

```
1. Ve a: https://console.cloud.google.com/
2. Crea un proyecto: "Professional Booking"
3. Habilita la API: Google Calendar API
4. Crea credenciales OAuth 2.0 (Desktop App)
5. Descarga el JSON de credenciales
```

### 2. Agregar en WordPress

```
Menú Admin → Reservas → Configuración → Google Calendar
```

| Campo | Valor |
|-------|-------|
| **Habilitar** | ✓ Sí |
| **Client ID** | De Google Cloud |
| **Client Secret** | De Google Cloud |
| **Refresh Token** | Generar (ver abajo) |
| **Calendar ID** | `primary` o ID específico |

### 3. Generar Refresh Token

```
1. Usa un script OAuth para generar el token
2. O usa herramientas como: OAuth 2.0 Playground de Google
3. Copia el refresh_token resultante
4. Pégalo en "Refresh Token"
```

### 4. Probar Conexión

Después de guardar, crea una nueva reserva confirmada. Debería aparecer automáticamente en tu calendario.

---

## Notificaciones por Email

### 1. Configurar Emails

```
Menú Admin → Reservas → Configuración → Notificaciones
```

### 2. Configurar "From"

| Campo | Descripción | Ejemplo |
|-------|-------------|---------|
| **From Address** | Email remitente | `reservas@miempresa.com` |
| **From Name** | Nombre remitente | `Mi Empresa` |

### 3. Emails al Cliente

```
Habilitar: ✓ Sí
Asunto: "Tu reserva ha sido confirmada"
Plantilla: [Personalizar]
```

**Variables disponibles:**
- `{{client_name}}` - Nombre del cliente
- `{{service_name}}` - Nombre del servicio
- `{{date}}` - Fecha de la reserva
- `{{time}}` - Hora de la reserva
- `{{site_name}}` - Nombre del sitio
- `{{video_link}}` - Enlace de videollamada

### 4. Emails al Admin

```
Habilitar: ✓ Sí
Email Admin: tu@email.com
Asunto: "Nueva reserva confirmada"
Plantilla: [Personalizar]
```

### 5. Guardar

Haz clic en "Guardar"

---

## Widget de Elementor

### 1. Requisito Previo

- Instala y activa **Elementor** (versión reciente)

### 2. Crear/Editar Página

```
1. Ve a: Páginas
2. Crear Nueva o editar existente
3. Abre Elementor (botón "Editar con Elementor")
```

### 3. Agregar Widget

```
1. Busca "Professional Booking" en widgets
2. Arrastra el widget a la página
3. Configura:
   - Selecciona servicio
   - Muestra info del servicio (Sí/No)
```

### 4. Publicar

Guarda y publica la página

### 5. Probar

```
1. Abre la página en el frontend
2. Verifica que se muestre el formulario de reserva
3. Intenta hacer una reserva de prueba
```

---

## Troubleshooting

### ¿No aparece el menú "Reservas"?

**Solución:**
```
1. Verifica que el plugin esté activado
2. Verifica tu rol de usuario (debe ser Admin)
3. Prueba desactivar y reactivar el plugin
```

### ¿Los horarios no se guardan?

**Solución:**
```
1. Verifica que hay espacio en la base de datos
2. Comprueba los permisos del servidor
3. Mira los logs del servidor (error_log)
```

### ¿No se envían emails?

**Solución:**
```
1. Prueba con un email test desde Configuración
2. Verifica SMTP del servidor
3. Comprueba dirección email "From"
4. Revisa logs de WordPress (debug.log)
```

### ¿El widget no aparece en Elementor?

**Solución:**
```
1. Verifica que Elementor esté activado
2. Verifica que el plugin esté activado
3. Limpia el caché
4. Regenera archivos de Elementor
```

### ¿Los pagos no funcionan?

**Solución:**
```
1. Verifica las credenciales API
2. Comprueba que estés en modo correcto (test/live)
3. Revisa que el servicio tenga precio
4. Verifica los logs de webhook
```

### ¿Google Calendar no sincroniza?

**Solución:**
```
1. Verifica que Google Calendar esté habilitado
2. Comprueba credenciales
3. Verifica que el refresh_token sea válido
4. Prueba crear una reserva confirmada
5. Revisa los logs
```

### ¿La zona horaria es incorrecta?

**Solución:**
```
1. Ve a: Configuración → Configuración → Zona horaria
2. Verifica que sea la correcta
3. Guarda cambios
4. Vacía caché si lo usas
```

---

## Datos de Prueba

### Credenciales de Prueba

**MercadoPago Sandbox:**
```
Tarjeta: 4111111111111111
Vencimiento: 11/25
CVV: 123
```

**Stripe Test:**
```
Tarjeta: 4242 4242 4242 4242
Vencimiento: 12/25
CVC: 123
```

**PayPal Sandbox:**
```
Usuario: Tu cuenta sandbox
Accede a: https://www.sandbox.paypal.com/
```

---

## Soporte y Documentación

- **README.md** - Información general del plugin
- **REVISION_AUDIT.md** - Auditoría de código
- **API REST** - Endpoints disponibles en REST API

---

## Próximos Pasos Recomendados

1. ✅ Configura datos generales
2. ✅ Crea tus servicios
3. ✅ Define tus horarios
4. ✅ Configura un método de pago
5. ✅ (Opcional) Integra Google Calendar
6. ✅ (Opcional) Configura emails personalizados
7. ✅ Crea una página con el widget
8. ✅ Haz una prueba de reserva

---

**¡Listo! Tu sistema de reservas está configurado y listo para usar.**

Para dudas o problemas, consulta la sección de Troubleshooting o revisa los logs del servidor.

*Última actualización: 30 de enero de 2026*
