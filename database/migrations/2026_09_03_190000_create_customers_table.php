<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone')->unique();
            $table->string('email')->nullable();
            $table->string('ip')->nullable();
            $table->json('utm')->nullable();

            // Order aggregates — not user-editable; defaulted here and kept
            // up to date by the future Orders module (see docs/BACKLOG.md).
            $table->decimal('total_orders_amount', 12, 2)->default(0);
            $table->timestamp('last_order_at')->nullable();
            $table->unsignedInteger('orders_count')->default(0);
            $table->unsignedInteger('completed_orders_count')->default(0);
            $table->unsignedInteger('cancelled_orders_count')->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
