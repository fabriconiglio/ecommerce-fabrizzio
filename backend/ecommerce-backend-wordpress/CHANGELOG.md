# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- `health:check` command scheduled to run daily and log to `storage/logs/health_check.log`.

## [1.0.0] - 2025-06-20

### Added
- **Initial Project Setup**: Backend API completa para E-commerce con Laravel 11.
- **API Endpoints**: Funcionalidad CRUD para Productos, Categorías, Carrito de Compras y Autenticación de Usuarios.
- **Authentication**: Sistema de registro y login seguro usando Laravel Sanctum.
- **External API Integration**: Servicios para consumir datos de Platzi Fake Store API (`PlatziApiService`) y RandomUser API (`RandomUserService`).
- **Custom Artisan Commands**:
  - `sync:products`: Para sincronizar productos desde una API externa.
  - `sync:categories`: Para sincronizar categorías desde una API externa.
  - `generate:users`: Para generar usuarios de prueba.
  - `health:check`: Para monitorear el estado del sistema y las APIs externas.
- **Custom Cache Service**: `CacheService` implementado para manejar el cache de la aplicación con un fallback a la base de datos para mayor resiliencia.
- **Database Structure**: Migraciones para `users`, `products`, `categories`, `cart_items` y `cache`.
- **Task Scheduling**: Tareas programadas en `routes/console.php` para sincronización de datos y chequeos de salud.
- **Documentation**: `README.md` detallado con instrucciones de instalación, endpoints de la API, ejemplos de uso y estructura del proyecto.

### Changed
- **README Formatting**: Se mejoró la legibilidad y estructura del `README.md` usando formato Markdown estándar.
- **Cache Logic**: Se refactorizó el `CacheService` para usar el facade `DB` directamente en lugar de un modelo Eloquent (`CacheEntry`), simplificando la lógica.

### Fixed
- **Namespace and Imports**: Corregidos múltiples errores de "Undefined type" en toda la aplicación (`Artisan`, `Hash`, `Category`, `DB`, `Log`, `User`, `EnsureEmailIsVerified`) añadiendo las declaraciones `use` y `namespace` faltantes.
- **Application Bootstrap**: Resuelto el error `BindingResolutionException` durante las pruebas al corregir la configuración y estructura de `bootstrap/app.php` y `routes/console.php`.
- **Artisan Command Registration**: Solucionado un `TypeError` al ejecutar comandos de Artisan, eliminando el registro manual e incorrecto de comandos en `routes/console.php`.
- **API Response**: Corregido un error de sintaxis en la respuesta JSON del método `checkout` en `CartController`.
- **Middleware Alias**: Solucionado un error de "Undefined type" para el middleware `EnsureEmailIsVerified` al apuntar a la clase correcta del framework. 