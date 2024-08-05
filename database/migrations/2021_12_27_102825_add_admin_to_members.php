<?php

declare(strict_types=1);

use App\Models\Organization;
use Illuminate\Database\Migrations\Migration;

class AddAdminToMembers extends Migration
{
    public function up(): void
    {
        Organization::each(static function (Organization $organization) {
            $organization->members()->syncWithoutDetaching([
                $organization->user_id => [
                    'position_id' => 1,
                    'permissions' => PHP_INT_MAX,
                ],
            ]);
        });
    }

    public function down(): void
    {
        // Nothing to do
    }
}
