<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTypeToMaterials extends Migration
{
    public function up(): void
    {
        Schema::table('materials', static function (Blueprint $table) {
            $table->string('type')->default('text')
                ->after('m_section_id');
        });
    }

    public function down(): void
    {
        Schema::table('materials', static function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
}
