<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNameToOrganizationChats extends Migration
{
    public function up(): void
    {
        Schema::table('organization_chats', static function (Blueprint $table) {
            $table->string('name')->nullable()
                ->after('organization_id');
            $table->text('data')->nullable()
                ->after('value');
        });
    }

    public function down(): void
    {
        Schema::table('organization_chats', static function (Blueprint $table) {
            $table->dropColumn(['name', 'data']);
        });
    }
}
