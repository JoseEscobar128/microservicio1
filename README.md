# 🍽️ Mesa Fácil — Módulo 1: Autenticación con 2FA (Laravel 11)

Bienvenido al repositorio del **Módulo 1** de **Mesa Fácil**, un sistema escalable de gestión para negocios gastronómicos.  
Este módulo implementa el **registro e inicio de sesión con autenticación en dos pasos (2FA)** y soporte para **OAuth federado propio**, todo desarrollado con **Laravel 11**.

> ⚠️ Este repositorio forma parte de una arquitectura modular y se integra con otros módulos funcionales de **Mesa Fácil**.

---

## ✨ Características destacadas

- ✅ Registro y login con verificación vía código 2FA (OTP).
- 🔐 Inicio de sesión mediante **OAuth2 federado propio**.
- 📬 Envío de correos (OTP) mediante [Resend](https://resend.com/).
- 🛡️ Autenticación segura con [Laravel Sanctum](https://laravel.com/docs/sanctum).
- 🔧 Gestión completa de empleados, usuarios, roles y permisos (Spatie).
- ♻️ Logout seguro y expiración de tokens.
- 📄 Respuestas estructuradas con códigos de error controlados por módulo.
- 📚 Código limpio, desacoplado y alineado a buenas prácticas Laravel.

---

## 🧱 Tecnologías y paquetes utilizados

| Herramienta / Paquete | Descripción |
|------------------------|-------------|
| **Laravel 11**         | Framework principal |
| **Sanctum**            | Autenticación vía tokens SPA/API |
| **Spatie Permission**  | Gestión avanzada de roles y permisos |
| **Resend**             | Servicio para envío de correos electrónicos |
| **OAuth2 federado propio** | Sistema personalizado de autorización |
| **MySQL**              | Base de datos relacional |
| **Laravel Form Requests** | Validación robusta de datos |
| **Laravel Logging**    | Registro de errores y eventos |

---

## 🧾 Módulos y funcionalidades

### 👤 Usuarios
- Registro con verificación 2FA.
- Validación de correo con código OTP.
- Reenvío de OTP y control de expiración.
- CRUD completo.
- Asociación a empleado (si aplica).
- Login federado vía OAuth personalizado.

### 👥 Empleados
- Registro de personal.
- Restauración de empleados eliminados (soft deletes).
- Validación de CURP, RFC, NSS y campos clave.
- Listado y edición protegida por roles.

### 🔐 Roles y permisos (Spatie)
- CRUD de roles.
- CRUD de permisos.
- Asignación y sincronización de permisos a roles.
- Asociación de roles a usuarios a través del empleado.

### 👤 Clientes
- Registro con verificación 2FA.
- Validación de correo con código OTP.
- Reenvío de OTP y control de expiración.
- CRUD completo.
- Login federado vía OAuth personalizado.

---

## ⚙️ Requisitos del sistema

- **PHP >= 8.2**
- **Composer >= 2.5**
- **Laravel 11**
- **MySQL 5.7+ o MariaDB**
- **Servidor SMTP o cuenta en [Resend](https://resend.com/)**

---

## 🚀 Instalación del proyecto

> 📌 Asegúrate de tener tu entorno local listo: PHP, Composer, MySQL y correo configurado (Resend o SMTP local).


# 1. Clonar el repositorio
git clone https://github.com/JoseEscobar128/mesa-facil.git
cd mesa-facil

# 2. Instalar dependencias
composer install

# 3. Copiar y configurar variables de entorno
cp .env.example .env

# 4. Generar clave de aplicación
php artisan key:generate

# 5. Configura el archivo .env
Base de datos (DB_DATABASE, DB_USERNAME, DB_PASSWORD)
Servicio de correo (RESEND_API_KEY, MAIL_FROM_ADDRESS, etc.)
Dominio de la app, nombre y otros detalles

# 6. Correr migraciones y seeders
php artisan migrate:fresh --seed

ℹ️ Importante: Los seeders son obligatorios, ya que insertan permisos, roles y relaciones base necesarias para que el sistema funcione correctamente.

# 7. Levantar servidor de desarrollo

php artisan serve

Accede a la API en http://localhost:8000.

## Endpoints disponibles

Aquí un resumen de los endpoints más relevantes de la API REST:

# CLientes

GET /api/v1/clientes
POST /api/v1/clientes/registro
POST /api/v1/clientes/verify-otp
POST /api/v1/clientes/reenviar-otp
POST /api/v1/clientes/logout
GET /api/v1/clientes/{id}
PUT /api/v1/clientes/{id}
DELETE /api/v1/clientes/{id}

# Usuarios

POST /api/v1/usuarios/register
POST /api/v1/usuarios/verify-otp
POST /api/v1/usuarios/resend-otp
POST /api/v1/usuarios/logout
GET /api/v1/usuarios
GET /api/v1/usuarios/{id}
PUT /api/v1/usuarios/{id}
DELETE /api/v1/usuarios/{id}

# Empleados

GET /api/v1/empleados
POST /api/v1/empleados/register
GET /api/v1/empleados/{id}
PUT /api/v1/empleados/{id}
DELETE /api/v1/empleados/{id}
# Roles y Permiso


GET /api/v1/roles
POST /api/v1/roles
GET /api/v1/roles/{id}
PUT /api/v1/roles/{id}
DELETE /api/v1/roles/{id}
PATCH /api/v1/roles/{id} (removePermission)


GET /api/v1/permisos
POST /api/v1/permisos
GET /api/v1/permisos/{id}
PUT /api/v1/permisos/{id}
DELETE /api/v1/permisos/{id}

# OAuth y Login

GET /login-cliente
POST /login-cliente
POST /api/v1/login
POST /api/v1/oauth2/token
GET /oauth/authorize
GET /otp-cliente
POST /verificar-otp-cliente
POST /cliente/resend-otp

##  Notas adicionales

- Todas las rutas protegidas requieren autenticación vía token Sanctum (Bearer Token).
- La mayoría de las acciones CRUD están sujetas a verificación de permisos (can:, hasRole, etc.).
- El guard utilizado para roles/permisos es fijo: empleado.
Si usas un cliente como Insomnia, recuerda:
Registrar usuario
Verificar OTP
Obtener token
Usarlo en los encabezados:
Authorization: Bearer <tu-token>


###  Autor

José Escobar
Desarrollador backend / UTT
GitHub: @JoseEscobar128

