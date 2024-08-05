<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUrgentFlagToDeskTasks extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('desk_tasks', static function (Blueprint $table) {
            $table->boolean('is_urgent')->default(false)
                ->after('can_self_assign');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('desk_tasks', static function (Blueprint $table) {
            $table->dropColumn(['is_urgent']);
        });
    }
}
