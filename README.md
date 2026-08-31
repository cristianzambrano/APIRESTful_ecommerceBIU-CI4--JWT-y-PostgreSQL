# API RESTful segura con CodeIgniter 4, JWT y PostgreSQL

**Asignatura:** Programming the Internet  
**Programa:** Master of Science in Computer Software Engineering  
**Tema:** Backend Development: API REST, autenticación y autorización por roles  
**Docente:** PhD. Cristian Zambrano-Vega

## 1. Propósito

API REST para un e-commerce utilizando PHP, Composer, CodeIgniter 4 y PostgreSQL. La API permite:

- Registrar usuarios y perfiles
- Iniciar sesión y generar un token JWT
- Validar JWT antes de ejecutar rutas protegidas
- Consultar dinámicamente si el rol del usuario puede ejecutar la acción solicitada
- Administrar productos según permisos definidos en la base de datos

La autorización NO está escrita únicamente con condiciones fijas en cada controlador. Se utilizan las tablas `roles`, `acciones` y `rol_accion`.

## 2. Reglas de negocio

| Rol        | Listar | Agregar | Editar | Eliminar |
|------------|--------|---------|--------|----------|
| `publico`  | ✅     | ❌      | ❌     | ❌       |
| `operador` | ✅     | ✅      | ✅     | ❌       |
| `supervisor` | ✅   | ✅      | ✅     | ✅       |

- El rol `publico` representa el acceso de lectura sin autenticación
- Para crear, editar o eliminar se requiere un JWT válido y un permiso activo en `rol_accion`

## 3. Requisitos previos

- **PHP 8.2 o superior** con extensiones: `curl`, `fileinfo`, `mbstring`, `openssl`, `pdo_pgsql`, `pgsql`, `intl`, `zip`
- **Composer** versión 2.x
- **PostgreSQL 15 o superior**
- **Postman** o **Insomnia** para pruebas
- **Editor de código** (VS Code recomendado)

Verificar instalación:

```bash
php -v
composer --version
psql --version
```

## 4. Instalación y configuración

### 4.1 Clonar o crear el proyecto

```bash
composer create-project codeigniter4/appstarter backend-ecommerce-api
cd backend-ecommerce-api
composer require firebase/php-jwt
```

### 4.2 Configurar archivo de entorno

```bash
copy env .env
```

En Linux/macOS:

```bash
cp env .env
```

Editar `.env` y configurar:

```ini
CI_ENVIRONMENT = development

database.default.hostname = localhost
database.default.database = ecommerce_api
database.default.username = postgres
database.default.password = TU_CLAVE_POSTGRES
database.default.DBDriver = Postgre
database.default.port = 5432
database.default.schema = public
database.default.charset = UTF8

jwt.secret = CAMBIAR_POR_UNA_CLAVE_LARGA_Y_ALEATORIA
jwt.ttl = 3600
```

**Importante:** No subir `.env` a GitHub. Verificar que esté en `.gitignore`.

### 4.3 Crear base de datos PostgreSQL

```sql
CREATE DATABASE ecommerce_api;
```

Ejecutar el script SQL completo desde `database/ecommerce_api.sql`.

## 5. Estructura del proyecto

```
app/
├── Controllers/
│   ├── API/
│   │   ├── AuthController.php       (Registro y login)
│   │   └── ProductosController.php  (CRUD de productos)
│   └── BaseController.php
├── Filters/
│   ├── JwtFilter.php               (Validación de JWT)
│   └── PermissionFilter.php        (Validación de permisos por rol)
├── Models/
│   ├── UsuarioModel.php
│   ├── PerfilUsuarioModel.php
│   └── ProductoModel.php
├── Config/
│   ├── Routes.php                  (Definición de rutas)
│   ├── Filters.php                 (Registro de filtros)
│   └── Database.php
└── [otros directorios estándar]
```

## 6. Ejecutar la aplicación

```bash
php spark serve
```

La API quedará disponible en `http://localhost:8080`.

Verificar estado:

```
GET http://localhost:8080/api/productos
```

## 7. Endpoints disponibles

### Autenticación

| Método | Ruta                 | Filtros | Descripción                |
|--------|----------------------|---------|----------------------------|
| POST   | `/api/auth/register` | —       | Registrar nuevo usuario    |
| POST   | `/api/auth/login`    | —       | Iniciar sesión             |

### Productos

| Método | Ruta                    | Filtros                             | Descripción          |
|--------|-------------------------|-------------------------------------|----------------------|
| GET    | `/api/productos`        | `permission:productos.listar`       | Listar (público)     |
| POST   | `/api/productos`        | `jwt`, `permission:productos.agregar` | Crear (operador+)   |
| PUT    | `/api/productos/:id`    | `jwt`, `permission:productos.editar` | Editar (operador+)  |
| DELETE | `/api/productos/:id`    | `jwt`, `permission:productos.eliminar` | Eliminar (supervisor) |

## 8. Ejemplos de uso

### Registrar usuario

```http
POST http://localhost:8080/api/auth/register
Content-Type: application/json

{
  "nombre": "Ana",
  "apellido": "López",
  "email": "ana@example.com",
  "password": "Segura2026*"
}
```

### Iniciar sesión

```http
POST http://localhost:8080/api/auth/login
Content-Type: application/json

{
  "email": "ana@example.com",
  "password": "Segura2026*"
}
```

Guardar el `access_token` para usarlo en las operaciones protegidas:

```http
Authorization: Bearer <access_token>
Content-Type: application/json
```

### Listar productos (público)

```http
GET http://localhost:8080/api/productos
```

### Agregar producto (requiere JWT)

```http
POST http://localhost:8080/api/productos
Authorization: Bearer <access_token>
Content-Type: application/json

{
  "nombre": "Monitor 24 pulgadas",
  "descripcion": "Monitor Full HD",
  "categoria": "Tecnología",
  "precio": 180.00,
  "stock": 8
}
```

### Editar producto

```http
PUT http://localhost:8080/api/productos/1
Authorization: Bearer <access_token>
Content-Type: application/json

{
  "nombre": "Monitor 24 pulgadas actualizado",
  "stock": 10
}
```

### Eliminar producto (solo supervisor)

```http
DELETE http://localhost:8080/api/productos/1
Authorization: Bearer <access_token>
```

## 9. Cadena de seguridad

```
Petición → JwtFilter → PermissionFilter → Controlador → PostgreSQL
```

- **JwtFilter:** Valida la presencia y validez del JWT
- **PermissionFilter:** Consulta `rol_accion` para autorizar la acción
- En rutas públicas solo se ejecuta `PermissionFilter` con rol lógico `publico`
- En rutas de escritura se ejecutan ambos filtros

## 10. Pruebas de autorización obligatorias

| Prueba | Resultado esperado |
|--------|-------------------|
| `GET /api/productos` sin token | `200` |
| `POST /api/productos` sin token | `401` |
| `POST /api/productos` con JWT de `publico` | `403` |
| `POST /api/productos` con JWT de `operador` | `201` |
| `PUT /api/productos/1` con JWT de `operador` | `200` |
| `DELETE /api/productos/1` con JWT de `operador` | `403` |
| `DELETE /api/productos/1` con JWT de `supervisor` | `200` |
| JWT vencido o alterado | `401` |
| Producto inexistente | `404` |
| Datos inválidos | `400` |

### Cambiar rol de usuario (solo en BD)

Para probar como `supervisor`:

```sql
UPDATE usuarios
SET rol_id = (SELECT id FROM roles WHERE nombre = 'supervisor')
WHERE email = 'ana@example.com';
```

Luego iniciar sesión nuevamente para obtener un JWT con el rol actualizado.

## 11. Decisiones de diseño

- **Eliminación lógica:** Los productos se marcan como `activo = false` en lugar de eliminarlos, para conservar el historial
- **Contraseñas:** Se almacenan con hash `PASSWORD_DEFAULT` (Argon2id)
- **Tokens JWT:** TTL configurables en `.env`, validados con HMAC-SHA256
- **Autorización dinámica:** Los permisos se consultan en `rol_accion`, permitiendo cambios sin redeployment

## 12. Entregables

- ✅ Repositorio GitHub con estructura correcta
- ✅ Script SQL `database/ecommerce_api.sql`
- ✅ Archivo `.env.example` sin claves reales
- ✅ Colección de Postman con todos los endpoints
- ✅ Controladores de autenticación y productos
- ✅ Filtros JWT y de permisos funcionales
- ✅ Modelos para usuarios, perfiles y productos
- ✅ Rutas RESTful configuradas
- ✅ Pruebas de seguridad (200, 201, 400, 401, 403, 404)
- ✅ README con instrucciones completas

## 13. Resultado de aprendizaje

Al finalizar, podrás desarrollar una API RESTful con CodeIgniter 4 conectada a PostgreSQL, implementar autenticación basada en JWT y aplicar autorización dinámica consultando permisos en la base de datos. La práctica relaciona `php spark`, modelos, controladores y rutas REST con principios de seguridad backend y control de acceso.

## Server Requirements

PHP version 8.2 or higher is required, with the following extensions installed:

- [intl](http://php.net/manual/en/intl.requirements.php)
- [mbstring](http://php.net/manual/en/mbstring.installation.php)

> [!WARNING]
> - The end of life date for PHP 7.4 was November 28, 2022.
> - The end of life date for PHP 8.0 was November 26, 2023.
> - The end of life date for PHP 8.1 was December 31, 2025.
> - If you are still using below PHP 8.2, you should upgrade immediately.
> - The end of life date for PHP 8.2 will be December 31, 2026.

Additionally, make sure that the following extensions are enabled in your PHP:

- json (enabled by default - don't turn it off)
- [mysqlnd](http://php.net/manual/en/mysqlnd.install.php) if you plan to use MySQL
- [libcurl](http://php.net/manual/en/curl.requirements.php) if you plan to use the HTTP\CURLRequest library
