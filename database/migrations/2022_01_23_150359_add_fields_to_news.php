<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldsToNews extends Migration
{
    public function up(): void
    {
        Schema::table('news', static function (Blueprint $table) {
            $table->boolean('visible')->default(true)
                ->after('published');
            $table->text('comment')->nullable()
                ->after('clicks');
        });
    }

    public function down(): void
    {
        Schema::table('news', static function (Blueprint $table) {
            $table->dropColumn(['visible', 'comment']);
        });
    }
}
