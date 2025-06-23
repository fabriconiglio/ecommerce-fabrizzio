<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RandomUserService
{
    private string $baseUrl;
    private CacheService $cache;
    private int $defaultTimeout = 15;

    public function __construct(CacheService $cache)
    {
        $this->baseUrl = config('services.randomuser.url', 'https://randomuser.me/api');
        $this->cache = $cache;
    }

    /**
     * Generar usuarios aleatorios
     */
    public function generateUsers(int $count = 1, array $options = []): array
    {
        $defaultOptions = [
            'results' => $count,
            'nat' => 'us,gb,es,fr', // Nacionalidades
            'inc' => 'name,email,login,picture,phone,location', // Campos incluidos
        ];

        $params = array_merge($defaultOptions, $options);
        $cacheKey = 'random_users_' . md5(serialize($params));
        $cacheTtl = config('cache.ttl.external_api', 1800); // 30 minutos

        return $this->cache->remember($cacheKey, $cacheTtl, function () use ($params) {
            try {
                $response = Http::timeout($this->defaultTimeout)
                    ->get($this->baseUrl, $params);

                if ($response->successful()) {
                    $data = $response->json();
                    return $data['results'] ?? [];
                }

                Log::warning('RandomUser API error', [
                    'status' => $response->status(),
                    'params' => $params
                ]);

                return [];
            } catch (\Exception $e) {
                Log::error('Error fetching users from RandomUser API', [
                    'error' => $e->getMessage(),
                    'params' => $params
                ]);
                return [];
            }
        });
    }

    /**
     * Generar un usuario específico con seed
     */
    public function generateUserWithSeed(string $seed): ?array
    {
        $cacheKey = "random_user_seed_{$seed}";
        $cacheTtl = config('cache.ttl.external_api', 1800);

        return $this->cache->remember($cacheKey, $cacheTtl, function () use ($seed) {
            $users = $this->generateUsers(1, ['seed' => $seed]);
            return $users[0] ?? null;
        });
    }

    /**
     * Transformar usuario de RandomUser a formato de nuestra DB
     */
    public function transformUserData(array $userData): array
    {
        return [
            'uuid' => $userData['login']['uuid'] ?? null,
            'username' => $userData['login']['username'] ?? null,
            'email' => $userData['email'] ?? null,
            'password' => bcrypt($userData['login']['password'] ?? 'password123'),
            'name' => ($userData['name']['first'] ?? '') . ' ' . ($userData['name']['last'] ?? ''),
            'first_name' => $userData['name']['first'] ?? null,
            'last_name' => $userData['name']['last'] ?? null,
            'avatar' => $userData['picture']['large'] ?? null,
            'phone' => $userData['phone'] ?? null,
            'address' => [
                'street' => ($userData['location']['street']['number'] ?? '') . ' ' . 
                           ($userData['location']['street']['name'] ?? ''),
                'city' => $userData['location']['city'] ?? null,
                'state' => $userData['location']['state'] ?? null,
                'country' => $userData['location']['country'] ?? null,
                'postcode' => $userData['location']['postcode'] ?? null,
            ],
            'is_active' => true,
        ];
    }

    /**
     * Health check de la API
     */
    public function isHealthy(): bool
    {
        try {
            $response = Http::timeout(10)->get($this->baseUrl . '?results=1');
            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }
}

