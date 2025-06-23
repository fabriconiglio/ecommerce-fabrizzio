<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Product;

class CartItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'product_id',
        'quantity',
        'price_snapshot',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'price_snapshot' => 'decimal:2',
    ];

    // Relación: Un item del carrito pertenece a un usuario
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relación: Un item del carrito pertenece a un producto
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // Accessors
    public function getSubtotalAttribute()
    {
        return $this->quantity * $this->price_snapshot;
    }

    public function getFormattedSubtotalAttribute()
    {
        return '$' . number_format($this->subtotal, 2);
    }

    // Scopes
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    // Métodos utilitarios
    public function updateQuantity(int $quantity)
    {
        if ($quantity <= 0) {
            return $this->delete();
        }
        
        return $this->update(['quantity' => $quantity]);
    }

    public function incrementQuantity(int $amount = 1)
    {
        return $this->update(['quantity' => $this->quantity + $amount]);
    }

    public function decrementQuantity(int $amount = 1)
    {
        $newQuantity = $this->quantity - $amount;
        
        if ($newQuantity <= 0) {
            return $this->delete();
        }
        
        return $this->update(['quantity' => $newQuantity]);
    }
}
