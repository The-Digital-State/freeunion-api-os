<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddChatToOrganizations extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', static function (Blueprint $table) {
            $table->string('chat')->nullable()
                ->after('phone');
        });
    }

    public function down(): void
    {
        Schema::table('organizations', static function (Blueprint $table) {
            $table->dropColumn('chat');
        });
    }
}
