<?php
// app/Models/Category.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'external_id',
        'name',
        'image',
    ];

    protected $casts = [
        'external_id' => 'integer',
    ];

    // Relación: Una categoría tiene muchos productos
    public function products()
    {
        return $this->hasMany(Product::class);
    }

    // Scope para categorías activas
    public function scopeActive($query)
    {
        return $query->whereHas('products', function ($q) {
            $q->where('is_active', true);
        });
    }

    // Accessor para imagen con fallback
    public function getImageUrlAttribute()
    {
        return $this->image ?: 'https://via.placeholder.com/300x200?text=' . urlencode($this->name);
    }
}