<?php

declare(strict_types=1);

use App\Models\DeskTask;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDeskLinkToSuggestions extends Migration
{
    public function up(): void
    {
        Schema::table('suggestions', static function (Blueprint $table) {
            $table->foreignIdFor(DeskTask::class)->nullable()
                ->after('user_id')
                ->constrained()
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('suggestions', static function (Blueprint $table) {
            $table->dropConstrainedForeignId('desk_task_id');
        });
    }
}
