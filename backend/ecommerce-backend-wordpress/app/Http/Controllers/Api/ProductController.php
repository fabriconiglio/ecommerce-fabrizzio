<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    /**
     * Lista de productos con filtros y paginación
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = min($request->get('per_page', 15), 50);
            $page = $request->get('page', 1);
            $offset = ($page - 1) * $perPage;
            
            $products = DB::table('products')
                ->join('categories', 'products.category_id', '=', 'categories.id')
                ->where('products.is_active', 1)
                ->where('products.stock', '>', 0)
                ->select(
                    'products.id',
                    'products.external_id',
                    'products.title',
                    'products.description',
                    'products.price',
                    'products.images',
                    'products.stock',
                    'products.created_at',
                    'categories.name as category_name',
                    'categories.id as category_id'
                )
                ->limit($perPage)
                ->offset($offset)
                ->get();

            $total = DB::table('products')
                ->where('is_active', 1)
                ->where('stock', '>', 0)
                ->count();

            $transformedProducts = $products->map(function($product) {
                $images = json_decode($product->images, true) ?? [];
                return [
                    'id' => $product->id,
                    'external_id' => $product->external_id,
                    'title' => $product->title,
                    'description' => $product->description,
                    'price' => $product->price,
                    'formatted_price' => '$' . number_format($product->price, 0),
                    'main_image' => $images[0] ?? 'https://via.placeholder.com/300x300',
                    'images' => $images,
                    'stock' => $product->stock,
                    'is_available' => true,
                    'category' => [
                        'id' => $product->category_id,
                        'name' => $product->category_name
                    ],
                    'created_at' => $product->created_at
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $transformedProducts,
                'meta' => [
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'total' => $total,
                    'last_page' => ceil($total / $perPage),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mostrar producto específico
     */
    public function show(string $id): JsonResponse
    {
        try {
            $product = DB::table('products')
                ->join('categories', 'products.category_id', '=', 'categories.id')
                ->where('products.id', $id)
                ->where('products.is_active', 1)
                ->select(
                    'products.*',
                    'categories.name as category_name'
                )
                ->first();

            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Producto no encontrado'
                ], 404);
            }

            $images = json_decode($product->images, true) ?? [];
            
            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $product->id,
                    'external_id' => $product->external_id,
                    'title' => $product->title,
                    'description' => $product->description,
                    'price' => $product->price,
                    'formatted_price' => '$' . number_format($product->price, 0),
                    'main_image' => $images[0] ?? 'https://via.placeholder.com/300x300',
                    'images' => $images,
                    'stock' => $product->stock,
                    'category' => [
                        'id' => $product->category_id,
                        'name' => $product->category_name
                    ],
                    'created_at' => $product->created_at
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Productos relacionados
     */
    public function related(string $id): JsonResponse
    {
        try {
            $product = DB::table('products')->where('id', $id)->first();
            
            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Producto no encontrado'
                ], 404);
            }

            $relatedProducts = DB::table('products')
                ->join('categories', 'products.category_id', '=', 'categories.id')
                ->where('products.category_id', $product->category_id)
                ->where('products.id', '!=', $id)
                ->where('products.is_active', 1)
                ->where('products.stock', '>', 0)
                ->limit(8)
                ->get();

            $transformedProducts = $relatedProducts->map(function($product) {
                $images = json_decode($product->images, true) ?? [];
                return [
                    'id' => $product->id,
                    'title' => $product->title,
                    'price' => $product->price,
                    'formatted_price' => '$' . number_format($product->price, 0),
                    'main_image' => $images[0] ?? 'https://via.placeholder.com/300x300',
                    'category' => [
                        'id' => $product->category_id,
                        'name' => $product->category_name
                    ],
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $transformedProducts
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Sincronizar productos
     */
    public function sync(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Sincronización no implementada aún'
        ]);
    }
}