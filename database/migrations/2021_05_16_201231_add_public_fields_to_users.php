<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPublicFieldsToUsers extends Migration
{
    public function up(): void
    {
        Schema::table('users', static function (Blueprint $table) {
            $table->string('public_name')
                ->after('remember_token')
                ->default('');
            $table->string('public_avatar')->nullable()
                ->after('public_name')
                ->default('');
        });
    }

    public function down(): void
    {
        Schema::table('users', static function (Blueprint $table) {
            $table->dropColumn(['public_name', 'public_avatar']);
        });
    }
}
