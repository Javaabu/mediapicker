<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Columns for javaabu/translatable's JSON storage on spatie's media table: harmless
 * without translatable. Runs after the media table exists (publish spatie's migration first).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('media')) {
            return;
        }

        Schema::table('media', function (Blueprint $table) {
            if (! Schema::hasColumn('media', 'translations')) {
                $table->json('translations')->nullable();
            }

            if (! Schema::hasColumn('media', 'lang')) {
                $table->string('lang')->nullable()->index();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('media') || ! Schema::hasColumn('media', 'translations')) {
            return;
        }

        Schema::table('media', function (Blueprint $table) {
            $table->dropIndex(['lang']);
            $table->dropColumn(['translations', 'lang']);
        });
    }
};
