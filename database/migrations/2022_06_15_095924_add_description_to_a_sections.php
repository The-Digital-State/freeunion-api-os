<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDescriptionToASections extends Migration
{
    public function up(): void
    {
        Schema::table('a_sections', static function (Blueprint $table) {
            $table->string('description')->nullable()
                ->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('a_sections', static function (Blueprint $table) {
            $table->dropColumn('description');
        });
    }
}
