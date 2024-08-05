<?php

declare(strict_types=1);

use App\Models\EnterRequest;
use App\Models\Organization;
use Illuminate\Database\Migrations\Migration;

class AddAdminToRequests extends Migration
{
    public function up(): void
    {
        Organization::each(static function (Organization $organization) {
            $enterRequest = $organization->enterRequests()->create(
                [
                    'user_id' => $organization->user_id,
                    'status' => EnterRequest::STATUS_ACTIVE,
                ]
            );

            $enterRequest->created_at = $organization->created_at;
            $enterRequest->updated_at = $organization->created_at;
            $enterRequest->save();
        });
    }

    public function down(): void
    {
        // Nothing to do
    }
}
