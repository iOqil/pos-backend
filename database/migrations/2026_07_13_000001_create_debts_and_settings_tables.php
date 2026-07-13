<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('debts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sale_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('amount', 12, 2); // Jami qarz summasi
            $table->decimal('paid_amount', 12, 2)->default(0); // To'langan summa
            $table->enum('status', ['active', 'paid'])->default('active');
            $table->date('due_date')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });

        // Telegram sozlamalari uchun
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('debts');
        Schema::dropIfExists('settings');
    }
};
