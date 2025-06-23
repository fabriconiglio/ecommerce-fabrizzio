<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\CartItem;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class CartController extends Controller
{
    /**
     * Obtener carrito del usuario
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $cartItems = $user->cartItems()
            ->with(['product' => function ($query) {
                $query->select('id', 'title', 'price', 'images', 'stock', 'is_active');
            }])
            ->orderBy('created_at', 'desc')
            ->get();

        $transformedItems = $cartItems->map(function ($item) {
            return $this->transformCartItem($item);
        });

        $total = $cartItems->sum('subtotal');
        $itemsCount = $cartItems->sum('quantity');

        return response()->json([
            'success' => true,
            'data' => [
                'items' => $transformedItems,
                'summary' => [
                    'items_count' => $itemsCount,
                    'total' => $total,
                    'formatted_total' => '$' . number_format($total, 2),
                ]
            ]
        ]);
    }

    /**
     * Agregar producto al carrito
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'quantity' => 'nullable|integer|min:1|max:10',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();
        $productId = $request->product_id;
        $quantity = $request->get('quantity', 1);

        // Verificar que el producto existe y está disponible
        $product = Product::find($productId);
        
        if (!$product || !$product->isAvailable()) {
            return response()->json([
                'success' => false,
                'message' => 'Producto no disponible'
            ], 404);
        }

        // Verificar stock
        $existingCartItem = $user->cartItems()->where('product_id', $productId)->first();
        $currentQuantityInCart = $existingCartItem ? $existingCartItem->quantity : 0;
        $totalQuantity = $currentQuantityInCart + $quantity;

        if ($totalQuantity > $product->stock) {
            return response()->json([
                'success' => false,
                'message' => 'Stock insuficiente',
                'data' => [
                    'available_stock' => $product->stock,
                    'current_in_cart' => $currentQuantityInCart,
                    'requested' => $quantity
                ]
            ], 400);
        }

        try {
            $cartItem = $user->addToCart($product, $quantity);

            return response()->json([
                'success' => true,
                'message' => 'Producto agregado al carrito',
                'data' => $this->transformCartItem($cartItem->fresh(['product']))
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al agregar al carrito',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar cantidad en el carrito
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'quantity' => 'required|integer|min:0|max:10',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Cantidad inválida',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();
        $cartItem = $user->cartItems()->find($id);

        if (!$cartItem) {
            return response()->json([
                'success' => false,
                'message' => 'Item no encontrado en el carrito'
            ], 404);
        }

        $quantity = $request->quantity;

        // Si la cantidad es 0, eliminar el item
        if ($quantity === 0) {
            $cartItem->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Producto eliminado del carrito'
            ]);
        }

        // Verificar stock disponible
        $product = $cartItem->product;
        if ($quantity > $product->stock) {
            return response()->json([
                'success' => false,
                'message' => 'Stock insuficiente',
                'data' => [
                    'available_stock' => $product->stock,
                    'requested' => $quantity
                ]
            ], 400);
        }

        try {
            $cartItem->updateQuantity($quantity);
            
            return response()->json([
                'success' => true,
                'message' => 'Cantidad actualizada',
                'data' => $this->transformCartItem($cartItem->fresh(['product']))
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar cantidad',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar producto del carrito
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $cartItem = $user->cartItems()->find($id);

        if (!$cartItem) {
            return response()->json([
                'success' => false,
                'message' => 'Item no encontrado en el carrito'
            ], 404);
        }

        try {
            $cartItem->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Producto eliminado del carrito'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar del carrito',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Limpiar todo el carrito
     */
    public function clear(Request $request): JsonResponse
    {
        $user = $request->user();

        try {
            $user->clearCart();
            
            return response()->json([
                'success' => true,
                'message' => 'Carrito limpiado'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al limpiar carrito',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Simular checkout (para demo)
     */
    public function checkout(Request $request): JsonResponse
    {
        $user = $request->user();
        $cartItems = $user->cartItems()->with('product')->get();

        if ($cartItems->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'El carrito está vacío'
            ], 400);
        }

        // Verificar stock para todos los productos
        $stockErrors = [];
        foreach ($cartItems as $item) {
            if ($item->quantity > $item->product->stock) {
                $stockErrors[] = [
                    'product' => $item->product->title,
                    'requested' => $item->quantity,
                    'available' => $item->product->stock
                ];
            }
        }

        if (!empty($stockErrors)) {
            return response()->json([
                'success' => false,
                'message' => 'Stock insuficiente para algunos productos',
                'data' => $stockErrors
            ], 400);
        }

        // Simular procesamiento de orden
        $total = $cartItems->sum('subtotal');
        $orderNumber = 'ORD-' . time() . '-' . $user->id;

        // En un e-commerce real, aquí se:
        // 1. Crearía la orden en la base de datos
        // 2. Reduciría el stock de los productos
        // 3. Procesaría el pago
        // 4. Enviaría emails de confirmación

        try {
            // Simular reducción de stock
            foreach ($cartItems as $item) {
                $item->product->decrement('stock', $item->quantity);
            }

            // Limpiar carrito después del checkout
            $user->clearCart();

            return response()->json([
                'success' => true,
                'message' => 'Orden procesada exitosamente',
                'data' => [
                    'order_number' => $orderNumber,
                    'total' => $total,
                    'formatted_total' => '$' . number_format($total, 2),
                    'items_count' => $cartItems->sum('quantity'),
                    'estimated_delivery' => now()->addDays(3)->format('Y-m-d'),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error procesando la orden',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Transformar item del carrito para la respuesta
     */
    private function transformCartItem(CartItem $cartItem): array
    {
        return [
            'id' => $cartItem->id,
            'quantity' => $cartItem->quantity,
            'price_snapshot' => $cartItem->price_snapshot,
            'subtotal' => $cartItem->subtotal,
            'formatted_subtotal' => $cartItem->formatted_subtotal,
            'product' => [
                'id' => $cartItem->product->id,
                'title' => $cartItem->product->title,
                'current_price' => $cartItem->product->price,
                'main_image' => $cartItem->product->main_image,
                'stock' => $cartItem->product->stock,
                'is_available' => $cartItem->product->isAvailable(),
            ],
            'added_at' => $cartItem->created_at->toISOString(),
        ];
    }
}