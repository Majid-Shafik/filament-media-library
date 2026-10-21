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
        Schema::create('pending_media_deletions', function (Blueprint $table) {
            $table->id();
            // Optional historical reference, strictly without foreign key constraint!
            $table->unsignedBigInteger('media_id')->nullable();
            $table->string('disk', 50)->default('public');
            $table->string('file_path', 500);
            $table->string('status', 20)->default('pending');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->string('claim_token', 64)->nullable();
            $table->timestamp('locked_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'locked_at', 'attempts'], 'idx_outbox_worker');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pending_media_deletions');
    }
};
