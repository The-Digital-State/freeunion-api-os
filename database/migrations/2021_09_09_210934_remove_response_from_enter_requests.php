<?php

declare(strict_types=1);

use App\Models\EnterRequest;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveResponseFromEnterRequests extends Migration
{
    public function up(): void
    {
        Schema::table('enter_requests', static function (Blueprint $table) {
            $table->dropColumn('response');

            $table->index(['user_id', 'organization_id']);
            $table->dropUnique(['user_id', 'organization_id']);
        });

        EnterRequest::query()->delete();
        Organization::query()->get()->each(static function (Organization $organization) {
            $organization->members()->each(static function (User $user) use ($organization) {
                EnterRequest::create([
                    'user_id' => $user->id,
                    'organization_id' => $organization->id,
                    'status' => EnterRequest::STATUS_ACTIVE,
                ]);
            });
        });
    }

    public function down(): void
    {
        Schema::table('enter_requests', static function (Blueprint $table) {
            $table->text('response')->nullable()
                ->after('status');

            $table->unique(['user_id', 'organization_id']);
            $table->dropIndex(['user_id', 'organization_id']);
        });

        EnterRequest::query()->delete();
        Organization::query()->get()->each(static function (Organization $organization) {
            $organization->members()->each(static function (User $user) use ($organization) {
                EnterRequest::create([
                    'user_id' => $user->id,
                    'organization_id' => $organization->id,
                    'status' => 1,
                ]);
            });
        });
    }
}
