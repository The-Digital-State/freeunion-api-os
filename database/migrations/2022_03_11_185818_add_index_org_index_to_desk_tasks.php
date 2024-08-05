<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIndexOrgIndexToDeskTasks extends Migration
{
    public function up(): void
    {
        Schema::table('desk_tasks', static function (Blueprint $table) {
            $table->index(['organization_id', 'column_id', 'index']);
        });
    }

    public function down(): void
    {
        Schema::table('desk_tasks', static function (Blueprint $table) {
            $table->dropIndex(['organization_id', 'column_id', 'index']);
        });
    }
}
