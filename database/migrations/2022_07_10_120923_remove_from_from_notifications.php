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
            $table->dropColumn(['from_type', 'from_id']);
        });
    }

    public function down(): void
    {
        Schema::table('notifications', static function (Blueprint $table) {
            $table->unsignedTinyInteger('from_type')->default(0)
                ->after('id');
            $table->unsignedBigInteger('from_id')->nullable()
                ->after('from_type');
        });
    }
};
