<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPermissionsToMembership extends Migration
{
    public function up(): void
    {
        Schema::table('membership', static function (Blueprint $table) {
            $table->unsignedBigInteger('permissions')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('membership', static function (Blueprint $table) {
            $table->dropColumn('permissions');
        });
    }
}
