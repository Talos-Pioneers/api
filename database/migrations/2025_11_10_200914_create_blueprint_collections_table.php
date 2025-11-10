<?php

use App\Models\Blueprint as BlueprintModel;
use App\Models\BlueprintCollection;
use App\Models\User;
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
        Schema::create('blueprint_collections', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('title');
            $table->string('slug')->unique();
            $table->foreignIdFor(User::class, 'creator_id');
            $table->text('description')->nullable();
            $table->string('status')->default('draft')->required();
            $table->boolean('is_anonymous')->default(false);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('blueprint_collection_blueprints', function (Blueprint $table) {
            $table->foreignIdFor(BlueprintModel::class);
            $table->foreignIdFor(BlueprintCollection::class);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blueprint_collections');
    }
};
