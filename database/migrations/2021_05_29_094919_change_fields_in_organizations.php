<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeFieldsInOrganizations extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', static function (Blueprint $table) {
            $table->dropColumn(['type', 'short_description']);
        });

        Schema::table('organizations', static function (Blueprint $table) {
            $table->foreignId('type_id')
                ->after('id')
                ->constrained('organization_types')
                ->cascadeOnDelete();
            $table->string('short_name')->after('name');
            $table->text('description')->after('avatar');
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            Schema::table('organizations', static function (Blueprint $table) {
                $table->dropForeign(['type_id']);
            });
        }

        Schema::table('organizations', static function (Blueprint $table) {
            $table->dropColumn(['short_name', 'description', 'type_id']);
        });

        Schema::table('organizations', static function (Blueprint $table) {
            $table->unsignedSmallInteger('type')->default(1);
            $table->text('short_description')->nullable();
        });
    }
}
