<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'uuid',
        'username',
        'first_name',
        'last_name',
        'avatar',
        'phone',
        'address',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'address' => 'array',
        'is_active' => 'boolean',
    ];

    // Relación: Un usuario puede tener muchos items en el carrito
    public function cartItems()
    {
        return $this->hasMany(CartItem::class);
    }

    // Relación: Productos en el carrito a través de cart_items
    public function cartProducts()
    {
        return $this->belongsToMany(Product::class, 'cart_items')
                    ->withPivot('quantity', 'price_snapshot')
                    ->withTimestamps();
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Accessors
    public function getFullNameAttribute()
    {
        return trim($this->first_name . ' ' . $this->last_name) ?: $this->name;
    }

    public function getAvatarUrlAttribute()
    {
        return $this->avatar ?: 'https://ui-avatars.com/api/?name=' . urlencode($this->full_name);
    }

    // Métodos del carrito
    public function getCartTotal()
    {
        return $this->cartItems()->sum(DB::raw('quantity * price_snapshot'));
    }

    public function getCartItemsCount()
    {
        return $this->cartItems()->sum('quantity');
    }

    public function addToCart(Product $product, int $quantity = 1)
    {
        $cartItem = $this->cartItems()->where('product_id', $product->id)->first();
        
        if ($cartItem) {
            $cartItem->update([
                'quantity' => $cartItem->quantity + $quantity,
                'price_snapshot' => $product->price, // Actualizar precio
            ]);
        } else {
            $this->cartItems()->create([
                'product_id' => $product->id,
                'quantity' => $quantity,
                'price_snapshot' => $product->price,
            ]);
        }
        
        return $cartItem ?? $this->cartItems()->where('product_id', $product->id)->first();
    }

    public function removeFromCart(Product $product)
    {
        return $this->cartItems()->where('product_id', $product->id)->delete();
    }

    public function clearCart()
    {
        return $this->cartItems()->delete();
    }
}