<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grocery_items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('unit', 32);
            $table->unsignedBigInteger('unit_price_cents');
            $table->unsignedInteger('stock_quantity')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['is_active', 'deleted_at', 'id'], 'groceries_catalog_index');
            $table->index('name');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE grocery_items ADD CONSTRAINT groceries_price_nonnegative CHECK (unit_price_cents >= 0)');
            DB::statement('ALTER TABLE grocery_items ADD CONSTRAINT groceries_stock_nonnegative CHECK (stock_quantity >= 0)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('grocery_items');
    }
};
