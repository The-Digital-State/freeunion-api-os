<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangePositionInMembership extends Migration
{
    public function up(): void
    {
        Schema::table('membership', static function (Blueprint $table) {
            $table->dropColumn('position');
        });

        Schema::table('membership', static function (Blueprint $table) {
            $table->string('position')->default('')
                ->after('organization_id');
        });
    }

    public function down(): void
    {
        Schema::table('membership', static function (Blueprint $table) {
            $table->dropColumn('position');
        });

        Schema::table('membership', static function (Blueprint $table) {
            $table->string('position')->nullable()
                ->after('organization_id');
        });
    }
}
