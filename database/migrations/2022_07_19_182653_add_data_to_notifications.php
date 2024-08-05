<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications', static function (Blueprint $table) {
            $table->text('data')->nullable()
                ->after('message');
        });
    }

    public function down(): void
    {
        Schema::table('notifications', static function (Blueprint $table) {
            $table->dropColumn(['data']);
        });
    }
};