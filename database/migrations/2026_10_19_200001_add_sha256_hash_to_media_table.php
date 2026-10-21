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
        Schema::table('media', function (Blueprint $table) {
            if (!Schema::hasColumn('media', 'sha256_hash')) {
                $table->string('sha256_hash', 64)->nullable()->after('file_size');
                $table->index('sha256_hash', 'idx_media_sha256_hash');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('media', function (Blueprint $table) {
            if (Schema::hasColumn('media', 'sha256_hash')) {
                $table->dropIndex('idx_media_sha256_hash');
                $table->dropColumn('sha256_hash');
            }
        });
    }
};
