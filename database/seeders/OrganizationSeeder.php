<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\ActivityScope;
use App\Models\EnterRequest;
use App\Models\HelpOffer;
use App\Models\InterestScope;
use App\Models\Organization;
use App\Models\User;
use Exception;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;

class OrganizationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     *
     * @throws Exception
     */
    public function run(): void
    {
        $scopes = ActivityScope::query()->pluck('id')->toArray();
        $interests = InterestScope::query()->pluck('id')->toArray();
        $helpOffers = HelpOffer::query()->pluck('id')->toArray();
        $users = User::query()->where('id', '>', 3)->pluck('id')->toArray();

        Organization::factory()->count(20)->create();
        Organization::all()->each(static function (Organization $organization) use (
            $scopes,
            $interests,
            $helpOffers,
            $users
        ) {
            $organization->activityScope()->attach(Arr::random($scopes, random_int(1, 5)));
            $organization->interestScope()->attach(Arr::random($interests, random_int(1, 3)));

            $members = [];

            foreach (Arr::random($users, random_int(4, 40)) as $userId) {
                $members[$userId] = [
                    'position_id' => null,
                    'points' => random_int(0, 10000),
                ];

                if (random_int(0, 3) === 0) {
                    $members[$userId]['position_id'] = random_int(2, 10);
                }
            }

            $organization->members()->attach($members);

            if ($organization->id <= 10) {
                $organization->request_type = Organization::REQUEST_TYPE_APPROVE;
                $organization->save();

                $organization->members()->get()->each(static function (User $user) use ($organization) {
                    EnterRequest::query()->create([
                        'user_id' => $user->id,
                        'organization_id' => $organization->id,
                        'status' => 1,
                    ]);
                });
            }

            $organization->members()->get()->each(static function (User $user) use ($organization, $helpOffers) {
                if (random_int(0, 1)) {
                    foreach (Arr::random($helpOffers, random_int(2, 5)) as $helpOfferId) {
                        $organization->helpOfferLinks()->create([
                            'user_id' => $user->id,
                            'help_offer_id' => $helpOfferId,
                        ]);
                    }
                }
            });
        });

        $hierarchy = [
            2 => 1,
            5 => 4,
            6 => 3,
            8 => 6,
            9 => 8,
            10 => 8,
            11 => 8,
            12 => 4,
            13 => 10,
            14 => 4,
            15 => 13,
            16 => 10,
            17 => 4,
            18 => 4,
            19 => 4,
            20 => 14,
        ];

        foreach ($hierarchy as $child => $parent) {
            Organization::find($parent)?->organizationChildren()->attach($child);
        }
    }
}
