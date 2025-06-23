<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CacheService
{
    private const DEFAULT_TTL = 3600; // 1 hora

    /**
     * Guardar en cache con TTL personalizado
     */
    public function put(string $key, $value, int $ttl = null): bool
    {
        $ttl = $ttl ?? self::DEFAULT_TTL;
        
        try {
            // Usar el cache driver de Laravel por defecto
            Cache::put($key, $value, $ttl);
            
            // También guardar en la tabla cache como backup
            DB::table('cache')->updateOrInsert(
                ['key' => $key],
                [
                    'value' => serialize($value),
                    'expiration' => time() + $ttl
                ]
            );
            
            return true;
        } catch (\Exception $e) {
            Log::error("Error guardando en cache: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener del cache
     */
    public function get(string $key, $default = null)
    {
        try {
            // Intentar obtener del cache de Laravel primero
            $value = Cache::get($key);
            
            if ($value !== null) {
                return $value;
            }
            
            // Si no está en cache, intentar obtener de la tabla
            $cacheEntry = DB::table('cache')
                ->where('key', $key)
                ->where('expiration', '>', time())
                ->first();
            
            if ($cacheEntry) {
                $value = unserialize($cacheEntry->value);
                // Restaurar en el cache de Laravel
                Cache::put($key, $value, $cacheEntry->expiration - time());
                return $value;
            }
            
            return $default;
        } catch (\Exception $e) {
            Log::error("Error obteniendo del cache: " . $e->getMessage());
            return $default;
        }
    }

    /**
     * Verificar si existe en cache
     */
    public function has(string $key): bool
    {
        return Cache::has($key) || 
               DB::table('cache')
                 ->where('key', $key)
                 ->where('expiration', '>', time())
                 ->exists();
    }

    /**
     * Eliminar del cache
     */
    public function forget(string $key): bool
    {
        Cache::forget($key);
        DB::table('cache')->where('key', $key)->delete();
        return true;
    }

    /**
     * Limpiar cache expirado
     */
    public function cleanExpired(): int
    {
        return DB::table('cache')->where('expiration', '<', time())->delete();
    }

    /**
     * Remember pattern - obtener o ejecutar callback
     */
    public function remember(string $key, int $ttl, callable $callback)
    {
        $value = $this->get($key);
        
        if ($value !== null) {
            return $value;
        }
        
        $value = $callback();
        $this->put($key, $value, $ttl);
        
        return $value;
    }
}
