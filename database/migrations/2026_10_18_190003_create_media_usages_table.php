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
        Schema::create('media_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('media_id')
                ->constrained('media')
                ->onDelete('restrict')
                ->onUpdate('cascade');
            $table->string('usable_type', 150);
            $table->unsignedBigInteger('usable_id');
            $table->string('field_name', 100);
            $table->string('usage_path', 255)->default('featured_image');
            $table->timestamps();

            $table->unique(
                ['usable_type', 'usable_id', 'field_name', 'usage_path'],
                'unique_usage_slot'
            );
            $table->index(['usable_type', 'usable_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media_usages');
    }
};
