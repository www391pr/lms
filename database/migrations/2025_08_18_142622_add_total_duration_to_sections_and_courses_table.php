<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('sections', function (Blueprint $table) {
            $table->integer('total_duration')->default(0);
        });

        Schema::table('courses', function (Blueprint $table) {
            $table->integer('total_duration')->default(0);
        });
    }

    public function down()
    {
        Schema::table('sections', function (Blueprint $table) {
            $table->dropColumn('total_duration');
        });

        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn('total_duration');
        });
    }
};
