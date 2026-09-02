<?php

namespace Database\Seeders;

use App\Models\GroceryItem;
use Illuminate\Database\Seeder;

class GroceryItemSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['name' => 'Premium Basmati Rice', 'unit' => '1 kg', 'unit_price_cents' => 12000, 'stock_quantity' => 25],
            ['name' => 'Sunflower Oil', 'unit' => '1 litre', 'unit_price_cents' => 15500, 'stock_quantity' => 18],
            ['name' => 'Masoor Dal', 'unit' => '1 kg', 'unit_price_cents' => 9500, 'stock_quantity' => 30],
            ['name' => 'Sugar (Chini)', 'unit' => '1 kg', 'unit_price_cents' => 7000, 'stock_quantity' => 40],
            ['name' => 'Potato', 'unit' => '1 kg', 'unit_price_cents' => 3500, 'stock_quantity' => 50],
            ['name' => 'Egg (Farm Fresh)', 'unit' => '6 pcs', 'unit_price_cents' => 7500, 'stock_quantity' => 24],
        ];

        foreach ($items as $item) {
            GroceryItem::query()->updateOrCreate(
                ['name' => $item['name']],
                $item + ['description' => null, 'is_active' => true]
            );
        }
    }
}
