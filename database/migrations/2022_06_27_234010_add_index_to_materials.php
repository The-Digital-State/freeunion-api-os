<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('materials', static function (Blueprint $table) {
            $table->integer('index')->default(0)
                ->after('m_section_id');
        });

        Schema::table('materials', static function (Blueprint $table) {
            $table->index(['organization_id', 'index']);
            $table->index(['m_section_id', 'index']);
        });
    }

    public function down(): void
    {
        Schema::table('materials', static function (Blueprint $table) {
            $table->dropIndex(['organization_id', 'index']);
            $table->dropIndex(['m_section_id', 'index']);
        });

        Schema::table('materials', static function (Blueprint $table) {
            $table->dropColumn(['index']);
        });
    }
};
