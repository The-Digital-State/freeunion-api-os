<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInterestScopeOrganizationTable extends Migration
{
    public function up(): void
    {
        if (App::environment('production')) {
            DB::statement('SET SQL_REQUIRE_PRIMARY_KEY = OFF;');
        }

        Schema::create('interest_scope_organization', static function (Blueprint $table) {
            $table->foreignId('interest_scope_id')->constrained()
                ->cascadeOnDelete();
            $table->foreignId('organization_id')->constrained()
                ->cascadeOnDelete();

            $table->primary(['interest_scope_id', 'organization_id'], 'interest_scope_organization_primary');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interest_scope_organization');
    }
}
