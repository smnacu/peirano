# Guía de Configuración: Microsoft 365 & Graph API

Esta guía te ayudará a conectar el Sistema de Turnos con tu calendario de Outlook/Microsoft 365.

## 1. Registrar la Aplicación en Azure
1.  Ingresa al [Portal de Azure](https://portal.azure.com/) e inicia sesión con tu cuenta de Microsoft 365.
2.  Busca y selecciona **"Microsoft Entra ID"** (antes Azure Active Directory).
3.  En el menú izquierdo, ve a **"Registros de aplicaciones"** (App registrations).
4.  Haz clic en **"+ Nuevo registro"**.
5.  **Nombre**: Ponle un nombre, ej: "Sistema de Turnos Peirano".
6.  **Tipos de cuenta compatibles**: Selecciona *"Cuentas en este directorio organizativo solo (Solo inquilino único)"*.
7.  **URI de redirección**: Déjalo en blanco por ahora (no usamos login de usuario, sino conexión directa).
8.  Haz clic en **"Registrar"**.

## 2. Obtener Credenciales (IDs)
Una vez creada la app, verás la pantalla de "Información general". Copia estos datos en tu `config.php`:

*   **ID de aplicación (cliente)** -> Es tu `CLIENT_ID`.
*   **ID de directorio (inquilino)** -> Es tu `TENANT_ID`.

## 3. Crear el Secreto (Contraseña)
1.  En el menú izquierdo de tu app, ve a **"Certificados y secretos"**.
2.  Ve a la pestaña **"Secretos de cliente"**.
3.  Haz clic en **"+ Nuevo secreto de cliente"**.
4.  **Descripción**: "Sistema Turnos".
5.  **Expira**: Elige el máximo tiempo posible (ej: 24 meses).
6.  Haz clic en **"Agregar"**.
7.  **IMPORTANTE**: Copia el **"Valor"** (Value) inmediatamente. Este es tu `CLIENT_SECRET`. *No podrás verlo después.*

## 4. Dar Permisos de Calendario
Para que el sistema pueda leer y escribir en el calendario sin pedir permiso cada vez:

1.  En el menú izquierdo, ve a **"Permisos de API"**.
2.  Haz clic en **"+ Agregar un permiso"**.
3.  Selecciona **"Microsoft Graph"**.
4.  Elige **"Permisos de la aplicación"** (NO permisos delegados).
5.  Busca y marca: `Calendars.ReadWrite`.
6.  Haz clic en **"Agregar permisos"**.
7.  **CRÍTICO**: Verás una advertencia. Haz clic en el botón **"Conceder consentimiento de administrador para [Tu Empresa]"** y confirma. Si no haces esto, no funcionará.

## 5. Configurar el Sistema
Abre el archivo `config.php` y pega los valores:

```php
define('TENANT_ID', 'tu-tenant-id-aqui');
define('CLIENT_ID', 'tu-client-id-aqui');
define('CLIENT_SECRET', 'tu-client-secret-aqui');
define('CALENDAR_USER_ID', 'correo@tuempresa.com'); // El correo del calendario a usar
```

¡Listo! El sistema ahora tiene permiso para gestionar el calendario de ese usuario.
