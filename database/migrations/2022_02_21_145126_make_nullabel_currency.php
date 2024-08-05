<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MakeNullabelCurrency extends Migration
{
    public function up(): void
    {
        Schema::table('fundraisings', static function (Blueprint $table) {
            $table->dropColumn('currency');
        });

        Schema::table('fundraisings', static function (Blueprint $table) {
            $table->string('currency')->nullable()
                ->after('ammount');
        });
    }

    public function down(): void
    {
        Schema::table('fundraisings', static function (Blueprint $table) {
            $table->dropColumn('currency');
        });

        Schema::table('fundraisings', static function (Blueprint $table) {
            $table->string('currency')->nullable()
                ->after('ammount');
        });
    }
}
