<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOrganizationToInviteLinks extends Migration
{
    public function up(): void
    {
        Schema::table('invite_links', static function (Blueprint $table) {
            $table->foreignId('organization_id')->nullable()
                ->after('user_id')
                ->constrained()->cascadeOnDelete();

            $table->softDeletes();
        });

        Schema::table('invite_links', static function (Blueprint $table) {
            $table->dropColumn(['invites', 'limit', 'updated_at']);
        });
    }

    public function down(): void
    {
        Schema::table('invite_links', static function (Blueprint $table) {
            $table->integer('invites')->nullable()
                ->after('code');
            $table->timestamp('limit')->nullable()
                ->after('invites');

            $table->timestamp('updated_at')->nullable()
                ->after('created_at');
        });

        Schema::table('invite_links', static function (Blueprint $table) {
            $table->dropConstrainedForeignId('organization_id');

            $table->dropSoftDeletes();
        });
    }
}
