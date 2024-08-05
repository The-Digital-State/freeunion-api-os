<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEnabledToBanners extends Migration
{
    public function up(): void
    {
        Schema::table('banners', static function (Blueprint $table) {
            $table->boolean('enabled')->default(true)
                ->after('index');
        });
    }

    public function down(): void
    {
        Schema::table('banners', static function (Blueprint $table) {
            $table->dropColumn(['enabled']);
        });
    }
}
