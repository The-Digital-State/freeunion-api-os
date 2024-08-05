<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOwnerFieldToOrganizations extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', static function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()
                ->after('id')
                ->constrained()
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            Schema::table('organizations', static function (Blueprint $table) {
                $table->dropForeign(['user_id']);
            });
        }

        Schema::table('organizations', static function (Blueprint $table) {
            $table->dropColumn('user_id');
        });
    }
}
