<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

// Health check
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toISOString(),
        'service' => 'Ecommerce Backend API',
        'version' => '1.0.0'
    ]);
});

// Public authentication routes
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

// Public product and category routes
Route::prefix('products')->group(function () {
    Route::get('/', [ProductController::class, 'index']);
    Route::get('/{id}', [ProductController::class, 'show']);
    Route::get('/{id}/related', [ProductController::class, 'related']);
    Route::post('/sync', [ProductController::class, 'sync']); // Para desarrollo
});

Route::prefix('categories')->group(function () {
    Route::get('/', [CategoryController::class, 'index']);
    Route::get('/{id}', [CategoryController::class, 'show']);
    Route::get('/{id}/products', [CategoryController::class, 'products']);
});

// Protected routes (require authentication)
Route::middleware('auth:sanctum')->group(function () {
    
    // User routes
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/user', [AuthController::class, 'user']);
    });
    
    // Cart routes
    Route::prefix('cart')->group(function () {
        Route::get('/', [CartController::class, 'index']);
        Route::post('/', [CartController::class, 'store']);
        Route::put('/{id}', [CartController::class, 'update']);
        Route::delete('/{id}', [CartController::class, 'destroy']);
        Route::delete('/', [CartController::class, 'clear']);
        Route::post('/checkout', [CartController::class, 'checkout']);
    });
});

// Development/Admin routes
Route::prefix('admin')->middleware('auth:sanctum')->group(function () {
    Route::post('/sync/products', function () {
        Artisan::call('sync:products', ['--limit' => 50]);
        return response()->json([
            'success' => true,
            'message' => 'Products sync initiated',
            'output' => Artisan::output()
        ]);
    });
    
    Route::post('/sync/categories', function () {
        Artisan::call('sync:categories');
        return response()->json([
            'success' => true,
            'message' => 'Categories sync initiated',
            'output' => Artisan::output()
        ]);
    });
    
    Route::post('/generate/users', function () {
        Artisan::call('generate:users', ['--count' => 10]);
        return response()->json([
            'success' => true,
            'message' => 'Users generation initiated',
            'output' => Artisan::output()
        ]);
    });
    
    Route::get('/health-check', function () {
        Artisan::call('health:check', ['--detailed' => true]);
        return response()->json([
            'success' => true,
            'output' => Artisan::output()
        ]);
    });

    
});

Route::get('/test/basic', function () {
    return response()->json([
        'message' => '✅ API funcionando correctamente',
        'timestamp' => now()->toISOString(),
        'laravel_version' => app()->version()
    ]);
});

Route::get('/test/external-apis', function () {
    $results = [];
    
    // Probar Platzi API
    try {
        $response = Http::timeout(10)->get('https://api.escuelajs.co/api/v1/products?limit=2');
        $results['platzi_api'] = [
            'status' => $response->successful() ? '✅ OK' : '❌ Error',
            'products_count' => $response->successful() ? count($response->json()) : 0
        ];
    } catch (\Exception $e) {
        $results['platzi_api'] = ['status' => '❌ Error: ' . $e->getMessage()];
    }
    
    // Probar RandomUser API
    try {
        $response = Http::timeout(10)->get('https://randomuser.me/api?results=1');
        $results['randomuser_api'] = [
            'status' => $response->successful() ? '✅ OK' : '❌ Error',
            'users_count' => $response->successful() ? count($response->json()['results'] ?? []) : 0
        ];
    } catch (\Exception $e) {
        $results['randomuser_api'] = ['status' => '❌ Error: ' . $e->getMessage()];
    }
    
    return response()->json([
        'message' => 'Prueba de APIs externas',
        'timestamp' => now()->toISOString(),
        'results' => $results
    ]);
});

// Ruta pública para probar guardado 
Route::post('/test/save-data-public', function () {
    $results = [];
    
    try {
        // 1. Probar si existen las tablas
        $tables_exist = [
            'categories' => Schema::hasTable('categories'),
            'products' => Schema::hasTable('products'),
            'users' => Schema::hasTable('users'),
        ];
        
        $results['tables_status'] = $tables_exist;
        
        // 2. Si las tablas existen, intentar guardar datos
        if ($tables_exist['categories']) {
            // Obtener categoría de la API
            $response = Http::get('https://api.escuelajs.co/api/v1/categories?limit=1');
            
            if ($response->successful()) {
                $categoryData = $response->json()[0];
                
                // Guardar directamente en la tabla (sin modelo por ahora)
                $categoryId = DB::table('categories')->insertGetId([
                    'external_id' => $categoryData['id'],
                    'name' => $categoryData['name'],
                    'image' => $categoryData['image'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                
                $results['category_saved'] = [
                    'id' => $categoryId,
                    'external_id' => $categoryData['id'],
                    'name' => $categoryData['name']
                ];
            }
        }
        
        // 3. Contar registros guardados
        $results['current_counts'] = [
            'categories' => DB::table('categories')->count(),
            'products' => DB::table('products')->count(),
            'users' => DB::table('users')->count(),
        ];
        
    } catch (\Exception $e) {
        $results['error'] = $e->getMessage();
    }
    
    return response()->json([
        'message' => 'Prueba de guardado en base de datos',
        'timestamp' => now()->toISOString(),
        'results' => $results
    ], 200, [], JSON_PRETTY_PRINT);
});