<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instructor_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 10, 2);
            $table->string('status')->default('pending'); // pending|paid|failed
            $table->string('stripe_transfer_id')->nullable();
            $table->string('idempotency_key')->nullable()->unique(); // safety for retries
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('payouts');
    }
};
