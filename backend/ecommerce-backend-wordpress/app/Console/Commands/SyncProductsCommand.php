<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\PlatziApiService;
use App\Models\Product;
use App\Models\Category;

class SyncProductsCommand extends Command
{
    protected $signature = 'sync:products 
                           {--force : Forzar actualización incluso si ya existen}
                           {--limit=100 : Límite de productos a procesar}
                           {--category= : ID de categoría específica a sincronizar}
                           {--offset=0 : Offset para paginación}';

    protected $description = 'Sincronizar productos desde la API de Platzi';

    private PlatziApiService $platziApi;

    public function __construct(PlatziApiService $platziApi)
    {
        parent::__construct();
        $this->platziApi = $platziApi;
    }

    public function handle(): int
    {
        $this->info('🔄 Iniciando sincronización de productos...');

        try {
            // Verificar conectividad
            if (!$this->platziApi->isHealthy()) {
                $this->error('❌ La API de Platzi no está disponible');
                return Command::FAILURE;
            }

            $filters = [
                'limit' => $this->option('limit'),
                'offset' => $this->option('offset'),
            ];

            if ($categoryId = $this->option('category')) {
                $filters['categoryId'] = $categoryId;
            }

            $products = $this->platziApi->getProducts($filters);
            
            if (empty($products)) {
                $this->warn('⚠️  No se encontraron productos en la API');
                return Command::SUCCESS;
            }

            $force = $this->option('force');
            $processed = 0;
            $created = 0;
            $updated = 0;
            $skipped = 0;
            $errors = 0;

            $progressBar = $this->output->createProgressBar(count($products));
            $progressBar->start();

            foreach ($products as $productData) {
                try {
                    if (!isset($productData['id'])) {
                        $skipped++;
                        $progressBar->advance();
                        continue;
                    }

                    // Buscar o crear la categoría
                    $category = null;
                    if (isset($productData['category']['id'])) {
                        $category = Category::where('external_id', $productData['category']['id'])->first();
                        
                        if (!$category) {
                            $category = Category::create([
                                'external_id' => $productData['category']['id'],
                                'name' => $productData['category']['name'] ?? 'Sin categoría',
                                'image' => $productData['category']['image'] ?? null,
                            ]);
                        }
                    }

                    $product = Product::where('external_id', $productData['id'])->first();

                    $productArray = [
                        'external_id' => $productData['id'],
                        'title' => $productData['title'] ?? 'Sin título',
                        'description' => $productData['description'] ?? null,
                        'price' => $productData['price'] ?? 0,
                        'images' => $productData['images'] ?? [],
                        'category_id' => $category?->id,
                        'is_active' => true,
                        'stock' => rand(0, 100), // Stock aleatorio para demo
                    ];

                    if ($product && !$force) {
                        $skipped++;
                    } elseif ($product && $force) {
                        $product->update($productArray);
                        $updated++;
                    } else {
                        Product::create($productArray);
                        $created++;
                    }

                    $processed++;

                } catch (\Exception $e) {
                    $this->error("Error procesando producto {$productData['id']}: " . $e->getMessage());
                    $errors++;
                }

                $progressBar->advance();
            }

            $progressBar->finish();
            $this->newLine(2);

            $this->info("✅ Sincronización completada:");
            $this->table(['Métrica', 'Cantidad'], [
                ['Procesados', $processed],
                ['Creados', $created],
                ['Actualizados', $updated],
                ['Omitidos', $skipped],
                ['Errores', $errors],
            ]);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Error durante la sincronización: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}

