<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RefactorPositionFieldsToMembership extends Migration
{
    public function up(): void
    {
        Schema::table('membership', static function (Blueprint $table) {
            $table->dropColumn(['position']);
        });

        Schema::table('membership', static function (Blueprint $table) {
            $table->foreignId('position_id')->nullable()
                ->after('organization_id')
                ->constrained('positions')
                ->cascadeOnDelete();
            $table->string('position_name')->nullable()
                ->after('position_id');
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            Schema::table('membership', static function (Blueprint $table) {
                $table->dropForeign(['position_id']);
            });
        }

        Schema::table('membership', static function (Blueprint $table) {
            $table->dropColumn(['position_name', 'position_id']);
        });

        Schema::table('membership', static function (Blueprint $table) {
            $table->string('position')->nullable()
                ->after('organization_id');
        });
    }
}
