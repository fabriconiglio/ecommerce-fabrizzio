<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\CacheService;
use App\Services\PlatziApiService;
use Illuminate\Support\Facades\DB;

class ClearCacheCommand extends Command
{
    protected $signature = 'cache:clear-custom 
                           {--expired : Solo limpiar cache expirado}
                           {--type= : Tipo específico (products, categories, users)}';

    protected $description = 'Limpiar cache personalizado del e-commerce';

    private CacheService $cacheService;
    private PlatziApiService $platziApi;

    public function __construct(CacheService $cacheService, PlatziApiService $platziApi)
    {
        parent::__construct();
        $this->cacheService = $cacheService;
        $this->platziApi = $platziApi;
    }

    public function handle(): int
    {
        $this->info('🧹 Limpiando cache personalizado...');

        try {
            $expiredOnly = $this->option('expired');
            $type = $this->option('type');

            if ($expiredOnly) {
                $cleared = $this->cacheService->cleanExpired();
                $this->info("✅ Se limpiaron {$cleared} entradas de cache expiradas");
            } elseif ($type) {
                switch ($type) {
                    case 'products':
                        // Limpiar cache de productos usando patrones
                        $cleared = $this->clearCacheByPattern(['platzi_products_', 'products_list_', 'product_detail_']);
                        $this->info("✅ Cache de productos limpiado ({$cleared} entradas)");
                        break;
                    case 'categories':
                        $cleared = $this->clearCacheByPattern(['platzi_categories', 'categories_list', 'category_detail_']);
                        $this->info("✅ Cache de categorías limpiado ({$cleared} entradas)");
                        break;
                    case 'users':
                        $cleared = $this->clearCacheByPattern(['random_users_', 'random_user_seed_']);
                        $this->info("✅ Cache de usuarios limpiado ({$cleared} entradas)");
                        break;
                    default:
                        $this->error("Tipo de cache no válido: {$type}");
                        $this->line("Tipos válidos: products, categories, users");
                        return Command::FAILURE;
                }
            } else {
                // Limpiar todo el cache
                $this->platziApi->clearCache();
                cache()->flush();
                $cleared = $this->cacheService->cleanExpired();
                
                $this->info("✅ Todo el cache ha sido limpiado");
                $this->info("📊 Entradas expiradas eliminadas: {$cleared}");
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Error limpiando cache: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Limpiar cache por patrones
     */
    private function clearCacheByPattern(array $patterns): int
    {
        $cleared = 0;
        
        foreach ($patterns as $pattern) {
            // Limpiar del cache de Laravel
            cache()->forget($pattern);
            
            // Limpiar de la tabla cache usando LIKE
            $cleared += DB::table('cache')
                ->where('key', 'like', $pattern . '%')
                ->delete();
        }
        
        return $cleared;
    }
}