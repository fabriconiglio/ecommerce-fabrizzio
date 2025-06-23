<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PlatziApiService
{
    private string $baseUrl;
    private CacheService $cache;
    private int $defaultTimeout = 30;

    public function __construct(CacheService $cache)
    {
        $this->baseUrl = config('services.platzi.url', 'https://api.escuelajs.co/api/v1');
        $this->cache = $cache;
    }

    /**
     * Obtener productos con filtros y paginación
     */
    public function getProducts(array $filters = []): array
    {
        $cacheKey = 'platzi_products_' . md5(serialize($filters));
        $cacheTtl = config('cache.ttl.products', 3600);

        return $this->cache->remember($cacheKey, $cacheTtl, function () use ($filters) {
            try {
                $response = Http::timeout($this->defaultTimeout)
                    ->get($this->baseUrl . '/products', $filters);

                if ($response->successful()) {
                    return $response->json();
                }

                Log::warning('Platzi API error', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);

                return [];
            } catch (\Exception $e) {
                Log::error('Error fetching products from Platzi API', [
                    'error' => $e->getMessage(),
                    'filters' => $filters
                ]);
                return [];
            }
        });
    }

    /**
     * Obtener producto por ID
     */
    public function getProduct(int $id): ?array
    {
        $cacheKey = "platzi_product_{$id}";
        $cacheTtl = config('cache.ttl.products', 3600);

        return $this->cache->remember($cacheKey, $cacheTtl, function () use ($id) {
            try {
                $response = Http::timeout($this->defaultTimeout)
                    ->get($this->baseUrl . "/products/{$id}");

                if ($response->successful()) {
                    return $response->json();
                }

                return null;
            } catch (\Exception $e) {
                Log::error('Error fetching product from Platzi API', [
                    'error' => $e->getMessage(),
                    'product_id' => $id
                ]);
                return null;
            }
        });
    }

    /**
     * Obtener categorías
     */
    public function getCategories(): array
    {
        $cacheKey = 'platzi_categories';
        $cacheTtl = config('cache.ttl.categories', 21600); // 6 horas

        return $this->cache->remember($cacheKey, $cacheTtl, function () {
            try {
                $response = Http::timeout($this->defaultTimeout)
                    ->get($this->baseUrl . '/categories');

                if ($response->successful()) {
                    return $response->json();
                }

                Log::warning('Platzi API categories error', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);

                return [];
            } catch (\Exception $e) {
                Log::error('Error fetching categories from Platzi API', [
                    'error' => $e->getMessage()
                ]);
                return [];
            }
        });
    }

    /**
     * Obtener productos por categoría
     */
    public function getProductsByCategory(int $categoryId, array $filters = []): array
    {
        $filters['categoryId'] = $categoryId;
        return $this->getProducts($filters);
    }

    /**
     * Autenticación (para testing)
     */
    public function authenticate(string $email, string $password): ?array
    {
        try {
            $response = Http::timeout($this->defaultTimeout)
                ->post($this->baseUrl . '/auth/login', [
                    'email' => $email,
                    'password' => $password
                ]);

            if ($response->successful()) {
                return $response->json();
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Error authenticating with Platzi API', [
                'error' => $e->getMessage(),
                'email' => $email
            ]);
            return null;
        }
    }

    /**
     * Limpiar cache relacionado con Platzi
     */
    public function clearCache(): void
    {
        $this->cache->forget('platzi_categories');
        
        // Limpiar productos (más complejo debido a los filtros)
        // En un entorno real, implementarías un sistema de tags
        Log::info('Platzi API cache cleared');
    }

    /**
     * Health check de la API
     */
    public function isHealthy(): bool
    {
        try {
            $response = Http::timeout(10)->get($this->baseUrl . '/products?limit=1');
            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }
}

