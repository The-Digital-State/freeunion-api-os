<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDescriptionToMembership extends Migration
{
    public function up(): void
    {
        Schema::table('membership', static function (Blueprint $table) {
            $table->text('description')->nullable()
                ->after('position_name');
        });
    }

    public function down(): void
    {
        Schema::table('membership', static function (Blueprint $table) {
            $table->dropColumn(['description']);
        });
    }
}
