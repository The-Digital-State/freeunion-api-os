<?php

declare(strict_types=1);

use App\Models\Organization;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTypeNameToOrganizations extends Migration
{
    public function up(): void
    {
        $typeIds = Organization::all()->pluck('type_id', 'id')->toArray();

        Schema::table('organizations', static function (Blueprint $table) {
            $table->dropConstrainedForeignId('type_id');
        });

        Schema::table('organizations', static function (Blueprint $table) {
            $table->foreignId('type_id')->nullable()
                ->after('user_id')
                ->constrained('organization_types')
                ->cascadeOnDelete();
            $table->string('type_name')->nullable()
                ->after('type_id');
        });

        foreach ($typeIds as $id => $typeId) {
            /** @var Organization|null $organization */
            $organization = Organization::query()->find($id);

            if ($organization) {
                $organization->type_id = $typeId;
                $organization->save();
            }
        }
    }

    public function down(): void
    {
        Schema::table('organizations', static function (Blueprint $table) {
            $table->dropColumn(['type_name']);
        });

        Schema::table('organizations', static function (Blueprint $table) {
            $table->unsignedBigInteger('type_id')->nullable(false)
                ->change();
        });
    }
}
