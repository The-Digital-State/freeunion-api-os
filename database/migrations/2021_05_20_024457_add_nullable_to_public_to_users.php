<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNullableToPublicToUsers extends Migration
{
    public function up(): void
    {
        Schema::table('users', static function (Blueprint $table) {
            $table->dropUnique(['public_family', 'public_name']);
        });

        Schema::table('users', static function (Blueprint $table) {
            $table->dropColumn(['public_family', 'public_name']);
        });

        Schema::table('users', static function (Blueprint $table) {
            $table->string('public_family', 32)->nullable()
                ->after('remember_token');
            $table->string('public_name', 32)->nullable()
                ->after('public_family');

            $table->unique(['public_family', 'public_name']);
        });
    }

    public function down(): void
    {
        Schema::table('users', static function (Blueprint $table) {
            $table->dropUnique(['public_family', 'public_name']);
        });

        Schema::table('users', static function (Blueprint $table) {
            $table->dropColumn(['public_family', 'public_name']);
        });

        Schema::table('users', static function (Blueprint $table) {
            $table->string('public_family')->nullable()
                ->after('remember_token');
            $table->string('public_name')->nullable()
                ->after('public_family');

            $table->unique(['public_family', 'public_name']);
        });
    }
}
