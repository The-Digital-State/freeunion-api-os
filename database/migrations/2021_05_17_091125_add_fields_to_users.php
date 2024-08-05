<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldsToUsers extends Migration
{
    public function up(): void
    {
        Schema::table('users', static function (Blueprint $table) {
            $table->dropColumn('name');
        });

        Schema::table('users', static function (Blueprint $table) {
            $table->string('login')
                ->after('id');

            $table->string('country', 2)
                ->after('public_avatar');
            $table->integer('worktype')
                ->after('country');
            $table->foreignId('scope')->nullable()
                ->after('worktype')
                ->constrained('activity_scopes')
                ->nullOnDelete();
            $table->string('work_place')
                ->after('scope');
            $table->text('about')
                ->after('work_place');
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            Schema::table('users', static function (Blueprint $table) {
                $table->dropForeign('scope');
            });
        }

        Schema::table('users', static function (Blueprint $table) {
            $table->dropColumn(['login', 'country', 'worktype', 'work_place', 'about', 'scope']);
        });

        Schema::table('users', static function (Blueprint $table) {
            $table->string('name')->default('')
                ->after('id');
        });
    }
}
