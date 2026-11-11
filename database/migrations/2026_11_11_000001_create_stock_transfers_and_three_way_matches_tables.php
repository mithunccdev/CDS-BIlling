<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('stock_transfers')) {
            Schema::create('stock_transfers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->nullable()->constrained()->onDelete('cascade');
                $table->string('transfer_no')->unique();
                $table->date('transfer_date');
                $table->foreignId('from_warehouse_id')->nullable()->constrained('warehouses')->onDelete('set null');
                $table->foreignId('to_warehouse_id')->nullable()->constrained('warehouses')->onDelete('set null');
                $table->foreignId('inventory_item_id')->nullable()->constrained('inventory_items')->onDelete('set null');
                $table->decimal('qty', 15, 2)->default(0.00);
                $table->string('status')->default('Completed');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('three_way_matches')) {
            Schema::create('three_way_matches', function (Blueprint $table) {
                $table->id();
                $table->foreignId('purchase_order_id')->constrained('purchase_orders')->onDelete('cascade');
                $table->foreignId('grn_id')->nullable()->constrained('goods_receipt_notes')->onDelete('set null');
                $table->string('vendor_invoice_no');
                $table->decimal('po_amount', 15, 2)->default(0.00);
                $table->decimal('grn_amount', 15, 2)->default(0.00);
                $table->decimal('invoice_amount', 15, 2)->default(0.00);
                $table->decimal('variance', 15, 2)->default(0.00);
                $table->boolean('is_matched')->default(false);
                $table->string('status')->default('Matched');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('three_way_matches');
        Schema::dropIfExists('stock_transfers');
    }
};

