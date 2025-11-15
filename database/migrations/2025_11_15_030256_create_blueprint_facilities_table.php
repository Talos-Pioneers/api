<?php

use App\Models\Blueprint;
use App\Models\Facility;
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
        Schema::create('blueprint_facilities', function (SchemaBlueprint $table) {
            $table->foreignIdFor(Blueprint::class);
            $table->foreignIdFor(Facility::class);
            $table->unsignedInteger('quantity')->default(1);
            $table->timestamps();

            $table->primary(['blueprint_id', 'facility_id']);
        });
    }
};
