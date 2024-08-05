<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateActivityScopeOrganizationTable extends Migration
{
    public function up(): void
    {
        if (App::environment('production')) {
            DB::statement('SET SQL_REQUIRE_PRIMARY_KEY = OFF;');
        }

        Schema::create('activity_scope_organization', static function (Blueprint $table) {
            $table->foreignId('activity_scope_id')->constrained()
                ->cascadeOnDelete();
            $table->foreignId('organization_id')->constrained()
                ->cascadeOnDelete();

            $table->primary(['activity_scope_id', 'organization_id'], 'activity_scope_organization_primary');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_scope_organization');
    }
}
