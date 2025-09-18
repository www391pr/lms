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
        Schema::table('instructors', function (Blueprint $table) {
            $table->boolean('enabled')->default(true);
            $table->decimal('current_balance', 10, 2)->default(0);
            $table->decimal('total_balance', 15, 2)->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('instructors', function (Blueprint $table) {
            $table->dropColumn(['enabled', 'current_balance', 'total_balance']);
        });
    }
};
