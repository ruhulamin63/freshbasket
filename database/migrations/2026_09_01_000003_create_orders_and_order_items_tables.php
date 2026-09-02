<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('status', 32);
            $table->unsignedBigInteger('total_amount_cents');
            $table->timestamp('placed_at');
            $table->timestamps();
            $table->index(['user_id', 'placed_at']);
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('grocery_item_id')->nullable()->constrained()->nullOnDelete();
            $table->string('item_name');
            $table->string('unit', 32);
            $table->unsignedBigInteger('unit_price_cents');
            $table->unsignedInteger('quantity');
            $table->unsignedBigInteger('subtotal_cents');
            $table->index('grocery_item_id');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE orders ADD CONSTRAINT orders_total_nonnegative CHECK (total_amount_cents >= 0)');
            DB::statement('ALTER TABLE order_items ADD CONSTRAINT order_items_quantity_positive CHECK (quantity > 0)');
            DB::statement('ALTER TABLE order_items ADD CONSTRAINT order_items_price_nonnegative CHECK (unit_price_cents >= 0 AND subtotal_cents >= 0)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
