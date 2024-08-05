<?php

declare(strict_types=1);

use App\Models\Organization;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class FixPartiallyPublicToOrganizations extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', static function (Blueprint $table) {
            $table->dropColumn(['is_public']);
        });

        Schema::table('organizations', static function (Blueprint $table) {
            $table->tinyInteger('public_status')->default(Organization::PUBLIC_STATUS_SHOW)
                ->after('registration');
        });
    }

    public function down(): void
    {
        Schema::table('organizations', static function (Blueprint $table) {
            $table->dropColumn(['public_status']);
        });

        Schema::table('organizations', static function (Blueprint $table) {
            $table->boolean('is_public')->default(1)
                ->after('registration');
        });
    }
}
