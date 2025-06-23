<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\PlatziApiService;
use App\Services\RandomUserService;
use App\Models\Product;
use App\Models\Category;
use App\Models\User;

class HealthCheckCommand extends Command
{
    protected $signature = 'health:check 
                           {--detailed : Mostrar información detallada}';

    protected $description = 'Verificar el estado de salud del sistema';

    private PlatziApiService $platziApi;
    private RandomUserService $randomUserService;

    public function __construct(PlatziApiService $platziApi, RandomUserService $randomUserService)
    {
        parent::__construct();
        $this->platziApi = $platziApi;
        $this->randomUserService = $randomUserService;
    }

    public function handle(): int
    {
        $this->info('🔍 Verificando estado del sistema...');
        $this->newLine();

        $allHealthy = true;

        // Verificar APIs externas
        $this->info('📡 APIs Externas:');
        $platziHealthy = $this->platziApi->isHealthy();
        $randomUserHealthy = $this->randomUserService->isHealthy();

        $this->line('  • Platzi API: ' . ($platziHealthy ? '✅ Disponible' : '❌ No disponible'));
        $this->line('  • RandomUser API: ' . ($randomUserHealthy ? '✅ Disponible' : '❌ No disponible'));

        if (!$platziHealthy || !$randomUserHealthy) {
            $allHealthy = false;
        }

        $this->newLine();

        // Verificar base de datos
        $this->info('💾 Base de Datos:');
        try {
            $productsCount = Product::count();
            $categoriesCount = Category::count();
            $usersCount = User::count();

            $this->line("  • Productos: {$productsCount}");
            $this->line("  • Categorías: {$categoriesCount}");
            $this->line("  • Usuarios: {$usersCount}");

            if ($productsCount === 0) {
                $this->warn('  ⚠️  No hay productos sincronizados. Ejecuta: php artisan sync:products');
                $allHealthy = false;
            }

            if ($categoriesCount === 0) {
                $this->warn('  ⚠️  No hay categorías sincronizadas. Ejecuta: php artisan sync:categories');
                $allHealthy = false;
            }

        } catch (\Exception $e) {
            $this->error('  ❌ Error conectando a la base de datos: ' . $e->getMessage());
            $allHealthy = false;
        }

        $this->newLine();

        // Verificar cache
        $this->info('🗄️  Sistema de Cache:');
        try {
            cache()->put('health_check_test', 'working', 60);
            $cacheTest = cache()->get('health_check_test');
            
            if ($cacheTest === 'working') {
                $this->line('  • Cache Laravel: ✅ Funcionando');
            } else {
                $this->error('  • Cache Laravel: ❌ No funciona');
                $allHealthy = false;
            }

            cache()->forget('health_check_test');
        } catch (\Exception $e) {
            $this->error('  • Cache Laravel: ❌ Error: ' . $e->getMessage());
            $allHealthy = false;
        }

        $this->newLine();

        // Información detallada
        if ($this->option('detailed')) {
            $this->info('📊 Información Detallada:');
            
            // Productos más recientes
            $recentProducts = Product::latest()->take(3)->get(['id', 'title', 'price', 'created_at']);
            if ($recentProducts->count() > 0) {
                $this->line('  • Últimos productos:');
                foreach ($recentProducts as $product) {
                    $this->line("    - {$product->title} (\${$product->price}) - {$product->created_at->diffForHumans()}");
                }
            }

            $this->newLine();

            // Categorías con más productos
            $categoriesWithProducts = Category::withCount('products')
                ->orderBy('products_count', 'desc')
                ->take(3)
                ->get();
            
            if ($categoriesWithProducts->count() > 0) {
                $this->line('  • Categorías principales:');
                foreach ($categoriesWithProducts as $category) {
                    $this->line("    - {$category->name}: {$category->products_count} productos");
                }
            }

            $this->newLine();
        }

        // Resumen final
        if ($allHealthy) {
            $this->info('🎉 Sistema funcionando correctamente');
            return Command::SUCCESS;
        } else {
            $this->error('⚠️  Se detectaron algunos problemas');
            return Command::FAILURE;
        }
    }
}