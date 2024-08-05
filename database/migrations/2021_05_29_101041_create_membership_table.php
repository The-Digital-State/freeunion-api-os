<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMembershipTable extends Migration
{
    public function up(): void
    {
        if (App::environment('production')) {
            DB::statement('SET SQL_REQUIRE_PRIMARY_KEY = OFF;');
        }

        Schema::create('membership', static function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()
                ->cascadeOnDelete();
            $table->foreignId('organization_id')->constrained()
                ->cascadeOnDelete();
            $table->string('position')->nullable();

            $table->primary(['user_id', 'organization_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('membership');
    }
}
