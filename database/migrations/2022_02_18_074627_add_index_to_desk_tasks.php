<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIndexToDeskTasks extends Migration
{
    public function up(): void
    {
        Schema::table('desk_tasks', static function (Blueprint $table) {
            $table->integer('index')->default(0)
                ->after('column_id');
        });
    }

    public function down(): void
    {
        Schema::table('desk_tasks', static function (Blueprint $table) {
            $table->dropColumn(['index']);
        });
    }
}
