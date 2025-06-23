# 🛒 E-commerce Backend API - Laravel 11

Backend API REST completo para e-commerce desarrollado con Laravel 11, integrado con APIs externas y sistema de caché.

## 🚀 Características

- ✅ API REST completa con endpoints para productos, categorías, usuarios y carrito
- ✅ Integración con APIs externas (Platzi Fake Store + RandomUser)
- ✅ Sistema de autenticación con Laravel Sanctum
- ✅ Cache inteligente para optimización de rendimiento
- ✅ Base de datos con migraciones y relaciones
- ✅ Comandos Artisan personalizados para sincronización de datos

## 📋 Requisitos

- PHP 8.2+
- Composer
- MySQL 8.0+
- Node.js (opcional)

## ⚡ Instalación Rápida

```bash
# Clonar repositorio
git clone [tu-repo]
cd ecommerce-backend

# Instalar dependencias
composer install

# Configurar entorno
cp .env.example .env
php artisan key:generate
```

A continuación, configura tus variables de base de datos en el archivo `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ecommerce_db
DB_USERNAME=root
DB_PASSWORD=
```

Finalmente, ejecuta las migraciones y levanta el servidor:

```bash
# Ejecutar migraciones
php artisan migrate

# Iniciar servidor
php artisan serve
```

## 🗄️ Estructura de Base de Datos

### Tablas Principales

-   `users` - Usuarios del sistema con autenticación
-   `categories` - Categorías de productos sincronizadas desde API externa
-   `products` - Productos con información completa y relaciones
-   `cart_items` - Items del carrito de compras por usuario
-   `cache` - Sistema de caché personalizado

### Relaciones

`User 1:N CartItem N:1 Product N:1 Category`

## 🌐 API Endpoints

### 🔓 Públicos (sin autenticación)

```http
# Health Check
GET /api/health

# Productos
GET /api/products                 # Lista con filtros y paginación
GET /api/products/{id}            # Detalle de producto
GET /api/products/{id}/related    # Productos relacionados

# Categorías
GET /api/categories               # Lista de categorías
GET /api/categories/{id}          # Detalle de categoría
GET /api/categories/{id}/products # Productos de una categoría

# Autenticación
POST /api/auth/register           # Registro de usuario
POST /api/auth/login              # Login de usuario
```

### 🔒 Protegidos (requieren autenticación)

```http
# Usuario autenticado
GET /api/auth/user                # Información del usuario
POST /api/auth/logout             # Logout

# Carrito de compras
GET /api/cart                     # Obtener carrito
POST /api/cart                    # Agregar producto
PUT /api/cart/{id}                # Actualizar cantidad
DELETE /api/cart/{id}             # Eliminar producto
DELETE /api/cart                  # Limpiar carrito
POST /api/cart/checkout           # Procesar compra
```

### 🧪 Endpoints de Prueba

```http
# Verificar funcionamiento
GET /api/test/basic               # Test básico
GET /api/test/external-apis       # Test APIs externas
POST /api/test/save-data-public   # Test guardado de datos
```

## 🔧 Comandos Artisan Disponibles

```bash
# Sincronización de datos
php artisan sync:categories       # Sincronizar categorías desde API
php artisan sync:products         # Sincronizar productos desde API
php artisan generate:users        # Generar usuarios de prueba

# Mantenimiento
php artisan health:check          # Verificar estado del sistema
php artisan cache:clear-custom    # Limpiar cache personalizado

# Ejemplos con opciones
php artisan sync:products --limit=100 --force
php artisan generate:users --count=20 --nationality=us,es
php artisan health:check --detailed
```

## 📊 Ejemplos de Uso

### Obtener productos con filtros

```bash
curl "http://localhost:8000/api/products?per_page=10&category_id=1&min_price=10&max_price=100&search=shirt"
```

### Registrar usuario

```bash
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Juan Pérez",
    "email": "juan@example.com",
    "password": "password123",
    "password_confirmation": "password123"
  }'
```

### Agregar producto al carrito

```bash
curl -X POST http://localhost:8000/api/cart \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"product_id": 1, "quantity": 2}'
```

## ⚙️ Configuración

### Variables de Entorno (.env)

```env
# APIs Externas
PLATZI_API_URL=https://api.escuelajs.co/api/v1
RANDOMUSER_API_URL=https://randomuser.me/api

# Cache TTL (segundos)
CACHE_TTL_PRODUCTS=3600
CACHE_TTL_CATEGORIES=21600
CACHE_TTL_EXTERNAL_API=1800

# CORS para frontend
FRONTEND_URL=http://localhost:3000
SANCTUM_STATEFUL_DOMAINS=localhost:3000,127.0.0.1:3000
```

### Cache

El sistema utiliza cache en múltiples capas:

-   **Laravel Cache** - Cache nativo de Laravel
-   **Database Cache** - Tabla `cache` personalizada
-   **API Cache** - Cache de respuestas de APIs externas

TTL por defecto:

-   Productos: 1 hora
-   Categorías: 6 horas
-   APIs externas: 30 minutos

## 🧪 Testing

### Health Check Completo

```bash
curl http://localhost:8000/api/test/status
```

### Verificar APIs Externas

```bash
curl http://localhost:8000/api/test/external-apis
```

### Probar Guardado de Datos

```bash
curl -X POST http://localhost:8000/api/test/save-data-public
```

## 🚀 Despliegue

### Preparar para Producción

```bash
# Optimizar para producción
composer install --no-dev --optimize-autoloader
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Configurar permisos
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### Variables de Producción

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tu-dominio.com

# Base de datos de producción
DB_HOST=tu-host-produccion
DB_DATABASE=ecommerce_prod
DB_USERNAME=tu-usuario-prod
DB_PASSWORD=tu-password-seguro

# Cache en producción (Redis recomendado)
CACHE_DRIVER=redis
REDIS_HOST=127.0.0.1
```

## 📁 Estructura del Proyecto

```
ecommerce-backend/
├── app/
│   ├── Console/Commands/          # Comandos Artisan personalizados
│   │   ├── SyncProductsCommand.php
│   │   ├── SyncCategoriesCommand.php
│   │   └── GenerateUsersCommand.php
│   ├── Http/Controllers/Api/      # Controladores API
│   │   ├── AuthController.php
│   │   ├── ProductController.php
│   │   ├── CategoryController.php
│   │   └── CartController.php
│   ├── Models/                    # Modelos Eloquent
│   │   ├── User.php
│   │   ├── Product.php
│   │   ├── Category.php
│   │   └── CartItem.php
│   └── Services/                  # Servicios personalizados
│       ├── CacheService.php
│       ├── PlatziApiService.php
│       └── RandomUserService.php
├── database/migrations/           # Migraciones de base de datos
├── routes/api.php                 # Rutas de la API
└── README.md                      # Esta documentación
```

## 🔍 Monitoreo y Logs

### Logs Importantes

```bash
# Logs generales
tail -f storage/logs/laravel.log

# Verificar estado del sistema
php artisan health:check --detailed
```

### Métricas del Sistema

El endpoint `/api/health` proporciona:

-   Estado de APIs externas
-   Conexión a base de datos
-   Funcionamiento del cache
-   Conteos de registros

## 🤝 Integración con Frontend

### Para WordPress + WooCommerce

El backend está diseñado para integrarse fácilmente con WordPress:

-   **Productos**: Consumir desde `/api/products`
-   **Categorías**: Usar `/api/categories`
-   **Autenticación**: Implementar login con `/api/auth/login`
-   **Carrito**: Sincronizar con `/api/cart`

### CORS Configurado

El backend permite peticiones desde:

-   `http://localhost:3000` (desarrollo)
-   `http://localhost:8080` (desarrollo)
-   URL configurada en `FRONTEND_URL`

## 📞 APIs Externas Utilizadas

### Platzi Fake Store API

-   **URL**: `https://api.escuelajs.co/api/v1`
-   **Uso**: Productos y categorías de muestra
-   **Rate Limit**: Sin límites conocidos
-   **Cache**: 1-6 horas según tipo de datos

### RandomUser API

-   **URL**: `https://randomuser.me/api`
-   **Uso**: Generación de usuarios de prueba
-   **Rate Limit**: ~1000 requests/día
-   **Cache**: 30 minutos

## 🔧 Troubleshooting

### Errores Comunes

**Error: "Column not found"**

```bash
php artisan migrate:fresh
```

**Error: "Class not found"**

```bash
composer dump-autoload
```

**Error: "API not responding"**

```bash
curl http://localhost:8000/api/health
```

### Cache no funciona

```bash
php artisan cache:clear
php artisan config:clear
```

## 📈 Próximos Pasos

-   [ ] Implementar rate limiting
-   [ ] Agregar tests automatizados
-   [ ] Optimizar queries con eager loading
-   [ ] Implementar logs estructurados
-   [ ] Agregar métricas de performance

## 👥 Contribuir

1.  Fork el proyecto
2.  Crear feature branch (`git checkout -b feature/nueva-funcionalidad`)
3.  Commit cambios (`git commit -am 'Agregar nueva funcionalidad'`)
4.  Push a la branch (`git push origin feature/nueva-funcionalidad`)
5.  Crear Pull Request

## 📄 Licencia

Este proyecto está bajo la Licencia MIT.