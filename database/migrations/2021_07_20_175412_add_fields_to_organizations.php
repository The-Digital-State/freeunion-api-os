<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldsToOrganizations extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', static function (Blueprint $table) {
            $table->string('site')->nullable()
                ->after('description');
            $table->string('email')->nullable()
                ->after('site');
            $table->string('address')->nullable()
                ->after('email');
            $table->string('phone')->nullable()
                ->after('address');

            $table->tinyInteger('registration')->default(0)
                ->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('organizations', static function (Blueprint $table) {
            $table->dropColumn(['site', 'email', 'address', 'phone', 'registration']);
        });
    }
}
