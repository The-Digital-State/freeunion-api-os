<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStatusToEnterRequests extends Migration
{
    public function up(): void
    {
        Schema::table('enter_requests', static function (Blueprint $table) {
            $table->tinyInteger('status')->default(0)
                ->after('comment');
            $table->text('response')->nullable()
                ->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('enter_requests', static function (Blueprint $table) {
            $table->dropColumn(['status', 'response']);
        });
    }
}
