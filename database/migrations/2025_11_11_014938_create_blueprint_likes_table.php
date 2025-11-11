<?php

use App\Models\Blueprint;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint as SchemaBlueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('blueprint_likes', function (SchemaBlueprint $table) {
            $table->foreignIdFor(User::class);
            $table->foreignIdFor(Blueprint::class);
            $table->timestamps();

            $table->primary(['user_id', 'blueprint_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blueprint_likes');
    }
};
