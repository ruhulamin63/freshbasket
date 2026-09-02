<?php

namespace App\Models;

use Database\Factories\GroceryItemFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class GroceryItem extends Model
{
    /** @use HasFactory<GroceryItemFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = ['name', 'description', 'unit', 'unit_price_cents', 'stock_quantity', 'is_active'];

    protected function casts(): array
    {
        return [
            'unit_price_cents' => 'integer',
            'stock_quantity' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    #[Scope]
    protected function available(Builder $query): void
    {
        $query->where('is_active', true)->where('stock_quantity', '>', 0);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
