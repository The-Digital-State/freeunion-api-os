<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTypeToNotifications extends Migration
{
    public function up(): void
    {
        Schema::table('notifications', static function (Blueprint $table) {
            $table->string('type')->default('')
                ->after('to_id');
            $table->text('title')->nullable()
                ->after('type');
        });
    }

    public function down(): void
    {
        Schema::table('notifications', static function (Blueprint $table) {
            $table->dropColumn(['type', 'title']);
        });
    }
}
