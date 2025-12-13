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
        Schema::table('blueprints', function (Blueprint $table) {
            $table->unsignedInteger('width')->nullable()->after('region');
            $table->unsignedInteger('height')->nullable()->after('width');
        });
    }
};
