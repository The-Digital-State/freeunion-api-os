<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('materials', static function (Blueprint $table) {
            $table->text('excerpt')->nullable()
                ->after('title');
        });
    }

    public function down(): void
    {
        Schema::table('materials', static function (Blueprint $table) {
            $table->dropColumn(['excerpt']);
        });
    }
};
