<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blueprints', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignIdFor(User::class, 'creator_id');
            $table->string('code')->required();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('version')->required();
            $table->text('description')->nullable();
            $table->string('status')->default('draft')->required();
            $table->string('region')->nullable();
            $table->boolean('is_anonymous')->default(false);
            $table->json('buildings')->nullable();
            $table->json('item_inputs')->nullable();
            $table->json('item_outputs')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }
};
