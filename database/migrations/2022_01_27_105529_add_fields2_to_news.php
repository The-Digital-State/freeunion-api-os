<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddFields2ToNews extends Migration
{
    public function up(): void
    {
        Schema::table('news', static function (Blueprint $table) {
            $table->dropColumn(['visible']);
        });

        Schema::table('news', static function (Blueprint $table) {
            $table->unsignedTinyInteger('visible')->default(0)
                ->after('preview');
            $table->timestamp('published_at')->nullable()
                ->after('updated_at');
        });

        DB::table('news')->where('published', true)
            ->update(['published_at' => DB::raw('updated_at')]);
    }

    public function down(): void
    {
        Schema::table('news', static function (Blueprint $table) {
            $table->dropColumn(['visible', 'published_at']);
        });

        Schema::table('news', static function (Blueprint $table) {
            $table->boolean('visible')->default(true)
                ->after('published');
        });
    }
}
