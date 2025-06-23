<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\PlatziApiService;
use App\Models\Category;

class SyncCategoriesCommand extends Command
{
    protected $signature = 'sync:categories 
                           {--force : Forzar actualización incluso si ya existen}
                           {--limit=50 : Límite de categorías a procesar}';

    protected $description = 'Sincronizar categorías desde la API de Platzi';

    private PlatziApiService $platziApi;

    public function __construct(PlatziApiService $platziApi)
    {
        parent::__construct();
        $this->platziApi = $platziApi;
    }

    public function handle(): int
    {
        $this->info('🔄 Iniciando sincronización de categorías...');

        try {
            // Verificar conectividad
            if (!$this->platziApi->isHealthy()) {
                $this->error('❌ La API de Platzi no está disponible');
                return Command::FAILURE;
            }

            $categories = $this->platziApi->getCategories();
            
            if (empty($categories)) {
                $this->warn('⚠️  No se encontraron categorías en la API');
                return Command::SUCCESS;
            }

            $limit = $this->option('limit');
            $force = $this->option('force');
            $processed = 0;
            $created = 0;
            $updated = 0;
            $skipped = 0;

            $progressBar = $this->output->createProgressBar(min(count($categories), $limit));
            $progressBar->start();

            foreach (array_slice($categories, 0, $limit) as $categoryData) {
                if (!isset($categoryData['id'])) {
                    $skipped++;
                    $progressBar->advance();
                    continue;
                }

                $category = Category::where('external_id', $categoryData['id'])->first();

                if ($category && !$force) {
                    $skipped++;
                } elseif ($category && $force) {
                    $category->update([
                        'name' => $categoryData['name'] ?? $category->name,
                        'image' => $categoryData['image'] ?? $category->image,
                    ]);
                    $updated++;
                } else {
                    Category::create([
                        'external_id' => $categoryData['id'],
                        'name' => $categoryData['name'] ?? 'Sin nombre',
                        'image' => $categoryData['image'] ?? null,
                    ]);
                    $created++;
                }

                $processed++;
                $progressBar->advance();
            }

            $progressBar->finish();
            $this->newLine(2);

            $this->info("✅ Sincronización completada:");
            $this->table(['Métrica', 'Cantidad'], [
                ['Procesadas', $processed],
                ['Creadas', $created],
                ['Actualizadas', $updated],
                ['Omitidas', $skipped],
            ]);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Error durante la sincronización: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
