<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateUserSecuresTable extends Migration
{
    public function up(): void
    {
        if (App::environment('production')) {
            DB::statement('SET SQL_REQUIRE_PRIMARY_KEY = OFF;');
        }

        Schema::create('user_secures', static function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()
                ->cascadeOnDelete();
            $table->longText('data')->nullable();

            $table->primary('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_secures');
    }
}
