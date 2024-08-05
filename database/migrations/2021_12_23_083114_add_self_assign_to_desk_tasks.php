<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSelfAssignToDeskTasks extends Migration
{
    public function up(): void
    {
        Schema::table('desk_tasks', static function (Blueprint $table) {
            $table->boolean('can_self_assign')->default(false)
                ->after('visibility');
        });
    }

    public function down(): void
    {
        Schema::table('desk_tasks', static function (Blueprint $table) {
            $table->dropColumn(['can_self_assign']);
        });
    }
}
