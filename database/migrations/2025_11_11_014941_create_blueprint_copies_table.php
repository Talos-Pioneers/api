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
        Schema::create('blueprint_copies', function (SchemaBlueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class)->nullable();
            $table->foreignIdFor(Blueprint::class);
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('copied_at');
            $table->timestamps();

            $table->index(['blueprint_id', 'user_id', 'copied_at']);
            $table->index(['blueprint_id', 'ip_address', 'copied_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blueprint_copies');
    }
};
