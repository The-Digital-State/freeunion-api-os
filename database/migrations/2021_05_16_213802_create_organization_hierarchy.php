<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateOrganizationHierarchy extends Migration
{
    public function up(): void
    {
        if (App::environment('production')) {
            DB::statement('SET SQL_REQUIRE_PRIMARY_KEY = OFF;');
        }

        Schema::create('organization_hierarchy', static function (Blueprint $table) {
            $table->foreignId('parent_id')->constrained('organizations')
                ->cascadeOnDelete();
            $table->foreignId('child_id')->constrained('organizations')
                ->cascadeOnDelete();

            $table->primary(['parent_id', 'child_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_hierarchy');
    }
}
