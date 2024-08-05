<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveFieldsFromUsers extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            Schema::table('users', static function (Blueprint $table) {
                $table->dropForeign(['scope']);
            });
        }

        Schema::table('users', static function (Blueprint $table) {
            $table->dropColumn(['country', 'worktype', 'work_place', 'sex', 'about', 'scope']);
        });
    }

    public function down(): void
    {
        Schema::table('users', static function (Blueprint $table) {
            $table->unsignedTinyInteger('sex')->default(0);
            $table->string('country', 2)->default('');
            $table->integer('worktype')->default(0);
            $table->foreignId('scope')->nullable()
                ->constrained('activity_scopes')
                ->nullOnDelete();
            $table->string('work_place')->default('');
            $table->text('about')->nullable();
        });
    }
}
