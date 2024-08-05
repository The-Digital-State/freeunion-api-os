<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddKbaseSectionDescriptionNull extends Migration
{
    public function up(): void
    {
        Schema::table('m_sections', static function (Blueprint $table) {
            $table->dropColumn('description');
        });

        Schema::table('m_sections', static function (Blueprint $table) {
            $table->string('description')->nullable()
                ->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('m_sections', static function (Blueprint $table) {
            $table->dropColumn('description');
        });

        Schema::table('m_sections', static function (Blueprint $table) {
            $table->string('description')->default('')
                ->after('name');
        });
    }
}
