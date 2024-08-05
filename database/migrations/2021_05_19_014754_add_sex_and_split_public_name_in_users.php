<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSexAndSplitPublicNameInUsers extends Migration
{
    public function up(): void
    {
        Schema::table('users', static function (Blueprint $table) {
            $table->string('public_family')
                ->after('remember_token');
            $table->unsignedTinyInteger('sex')
                ->after('work_place');

            $table->unique(['public_family', 'public_name']);
        });
    }

    public function down(): void
    {
        Schema::table('users', static function (Blueprint $table) {
            $table->dropUnique(['public_family', 'public_name']);
        });

        Schema::table('users', static function (Blueprint $table) {
            $table->dropColumn(['public_family', 'sex']);
        });
    }
}
