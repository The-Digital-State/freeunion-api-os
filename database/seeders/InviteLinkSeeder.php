<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\InviteLink;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class InviteLinkSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        // Admin1 invite
        /** @var InviteLink $link */
        $link = InviteLink::query()->create([
            'user_id' => 2,
            'created_at' => Carbon::now()->addYear(),
        ]);
        $link->code = 'A111111111';
        $link->save();

        // Admin2 invite
        /** @var InviteLink $link */
        $link = InviteLink::query()->create([
            'user_id' => 3,
            'created_at' => Carbon::now()->addYear(),
        ]);
        $link->code = 'B222222222';
        $link->save();
    }
}
