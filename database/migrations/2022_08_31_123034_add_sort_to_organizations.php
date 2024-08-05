<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', static function (Blueprint $table) {
            $table->unsignedBigInteger('sort')->default(0)
                ->after('hiddens');

            $table->index(['sort', 'id']);
        });
    }

    public function down(): void
    {
        Schema::table('organizations', static function (Blueprint $table) {
            $table->dropIndex(['sort', 'id']);

            $table->dropColumn(['sort']);
        });
    }
};
