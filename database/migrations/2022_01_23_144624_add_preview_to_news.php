<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPreviewToNews extends Migration
{
    public function up(): void
    {
        Schema::table('news', static function (Blueprint $table) {
            $table->string('preview')->nullable()
                ->after('content');
        });
    }

    public function down(): void
    {
        Schema::table('news', static function (Blueprint $table) {
            $table->dropColumn(['preview']);
        });
    }
}
