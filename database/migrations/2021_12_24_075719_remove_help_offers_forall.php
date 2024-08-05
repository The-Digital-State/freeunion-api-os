<?php

declare(strict_types=1);

use App\Models\HelpOffer;
use App\Models\Organization;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class RemoveHelpOffersForall extends Migration
{
    public function up(): void
    {
        DB::table('help_offers')->delete();

        DB::beginTransaction();
        Organization::query()->get()->each(static function (Organization $organization) {
            foreach (HelpOffer::defaultHelpOffers() as $item) {
                DB::table('help_offers')->insert([
                    [
                        'text' => $item,
                        'organization_id' => $organization->id,
                        'enabled' => true,
                    ],
                ]);
            }
        });
        DB::commit();
    }

    public function down(): void
    {
        // Nothing to do
    }
}
