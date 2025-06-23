<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Services\CacheService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    private CacheService $cache;

    public function __construct(CacheService $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Lista de categorías
     */
    public function index(): JsonResponse
    {
        $categories = $this->cache->remember('categories_list', 21600, function () {
            return Category::withCount(['products' => function ($query) {
                $query->active()->inStock();
            }])
            ->having('products_count', '>', 0)
            ->orderBy('name')
            ->get();
        });

        $transformedCategories = $categories->map(function ($category) {
            return [
                'id' => $category->id,
                'external_id' => $category->external_id,
                'name' => $category->name,
                'image_url' => $category->image_url,
                'products_count' => $category->products_count,
                'created_at' => $category->created_at->toISOString(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $transformedCategories
        ]);
    }

    /**
     * Mostrar categoría específica
     */
    public function show(string $id): JsonResponse
    {
        $cacheKey = "category_detail_{$id}";

        $category = $this->cache->remember($cacheKey, 3600, function () use ($id) {
            return Category::withCount(['products' => function ($query) {
                $query->active()->inStock();
            }])
            ->where('id', $id)
            ->orWhere('external_id', $id)
            ->first();
        });

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Categoría no encontrada'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $category->id,
                'external_id' => $category->external_id,
                'name' => $category->name,
                'image_url' => $category->image_url,
                'products_count' => $category->products_count,
                'created_at' => $category->created_at->toISOString(),
                'updated_at' => $category->updated_at->toISOString(),
            ]
        ]);
    }

    /**
     * Productos de una categoría
     */
    public function products(string $id, Request $request): JsonResponse
    {
        $category = Category::find($id);

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Categoría no encontrada'
            ], 404);
        }

        $perPage = min($request->get('per_page', 15), 50);
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');

        $cacheKey = "category_products_{$id}_" . md5(serialize($request->query()));

        $result = $this->cache->remember($cacheKey, 1800, function () use (
            $category, $perPage, $sortBy, $sortOrder
        ) {
            $query = $category->products()
                ->with('category')
                ->active()
                ->inStock();

            $allowedSorts = ['created_at', 'price', 'title'];
            if (in_array($sortBy, $allowedSorts)) {
                $query->orderBy($sortBy, $sortOrder === 'asc' ? 'asc' : 'desc');
            }

            return $query->paginate($perPage);
        });

        $products = $result->getCollection()->map(function ($product) {
            return [
                'id' => $product->id,
                'title' => $product->title,
                'price' => $product->price,
                'formatted_price' => $product->formatted_price,
                'main_image' => $product->main_image,
                'is_available' => $product->isAvailable(),
                'stock' => $product->stock,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $products,
            'category' => [
                'id' => $category->id,
                'name' => $category->name,
                'image_url' => $category->image_url,
            ],
            'meta' => [
                'current_page' => $result->currentPage(),
                'per_page' => $result->perPage(),
                'total' => $result->total(),
                'last_page' => $result->lastPage(),
            ]
        ]);
    }
}