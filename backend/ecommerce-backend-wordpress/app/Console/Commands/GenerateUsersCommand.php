<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\RandomUserService;
use App\Models\User;

class GenerateUsersCommand extends Command
{
    protected $signature = 'generate:users 
                           {--count=10 : Número de usuarios a generar}
                           {--nationality=us,gb,es : Nacionalidades separadas por coma}
                           {--force : Sobrescribir usuarios existentes}';

    protected $description = 'Generar usuarios de prueba usando RandomUser API';

    private RandomUserService $randomUserService;

    public function __construct(RandomUserService $randomUserService)
    {
        parent::__construct();
        $this->randomUserService = $randomUserService;
    }

    public function handle(): int
    {
        $this->info('👥 Generando usuarios de prueba...');

        try {
            // Verificar conectividad
            if (!$this->randomUserService->isHealthy()) {
                $this->error('❌ La API de RandomUser no está disponible');
                return Command::FAILURE;
            }

            $count = (int) $this->option('count');
            $nationalities = explode(',', $this->option('nationality'));
            $force = $this->option('force');

            $options = [
                'nat' => implode(',', $nationalities),
                'inc' => 'name,email,login,picture,phone,location,dob',
            ];

            // Generar usuarios en lotes de 50 (límite de la API)
            $batchSize = min(50, $count);
            $totalBatches = ceil($count / $batchSize);
            $created = 0;
            $skipped = 0;
            $errors = 0;

            $this->info("Generando {$count} usuarios en {$totalBatches} lote(s)...");

            for ($batch = 0; $batch < $totalBatches; $batch++) {
                $remaining = $count - ($batch * $batchSize);
                $currentBatchSize = min($batchSize, $remaining);

                $this->info("Procesando lote " . ($batch + 1) . "/{$totalBatches} ({$currentBatchSize} usuarios)");

                $users = $this->randomUserService->generateUsers($currentBatchSize, $options);

                $progressBar = $this->output->createProgressBar(count($users));
                $progressBar->start();

                foreach ($users as $userData) {
                    try {
                        $transformedData = $this->randomUserService->transformUserData($userData);
                        
                        // Verificar si el usuario ya existe
                        $existingUser = User::where('email', $transformedData['email'])
                            ->orWhere('username', $transformedData['username'])
                            ->first();

                        if ($existingUser && !$force) {
                            $skipped++;
                        } elseif ($existingUser && $force) {
                            $existingUser->update($transformedData);
                            $created++; // Contar como creado para simplificar
                        } else {
                            User::create($transformedData);
                            $created++;
                        }

                    } catch (\Exception $e) {
                        $this->error("Error creando usuario: " . $e->getMessage());
                        $errors++;
                    }

                    $progressBar->advance();
                }

                $progressBar->finish();
                $this->newLine();

                // Pausa entre lotes para no sobrecargar la API
                if ($batch < $totalBatches - 1) {
                    sleep(1);
                }
            }

            $this->newLine();
            $this->info("✅ Generación completada:");
            $this->table(['Métrica', 'Cantidad'], [
                ['Creados/Actualizados', $created],
                ['Omitidos', $skipped],
                ['Errores', $errors],
            ]);

            // Mostrar algunos usuarios creados
            $this->info("👤 Últimos usuarios creados:");
            $recentUsers = User::latest()->take(5)->get(['name', 'email', 'username']);
            $this->table(['Nombre', 'Email', 'Username'], 
                $recentUsers->map(fn($user) => [$user->name, $user->email, $user->username])->toArray()
            );

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Error durante la generación: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}