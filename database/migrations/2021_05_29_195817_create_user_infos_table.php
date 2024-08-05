<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserInfosTable extends Migration
{
    public function up(): void
    {
        if (App::environment('production')) {
            DB::statement('SET SQL_REQUIRE_PRIMARY_KEY = OFF;');
        }

        Schema::create('user_infos', static function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()
                ->cascadeOnDelete();
            $table->string('family')->nullable();
            $table->string('name')->nullable();
            $table->string('patronymic')->nullable();
            $table->unsignedTinyInteger('sex');
            $table->date('birthday')->nullable();
            $table->string('country', 2);
            $table->integer('worktype');
            $table->foreignId('scope')->nullable()
                ->constrained('activity_scopes')
                ->nullOnDelete();
            $table->string('work_place');
            $table->string('work_position')->nullable();
            $table->string('address')->nullable();
            $table->string('phone', 16)->nullable();
            $table->text('about')->nullable();

            $table->primary('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_infos');
    }
}
