<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPartiallyPublicToOrganizations extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', static function (Blueprint $table) {
            $table->dropColumn(['is_public']);
        });

        Schema::table('organizations', static function (Blueprint $table) {
            $table->tinyInteger('is_public')->default(1)
                ->after('registration');
        });
    }

    public function down(): void
    {
        Schema::table('organizations', static function (Blueprint $table) {
            $table->dropColumn(['is_public']);
        });

        Schema::table('organizations', static function (Blueprint $table) {
            $table->boolean('is_public')->default(1)
                ->after('registration');
        });
    }
}
