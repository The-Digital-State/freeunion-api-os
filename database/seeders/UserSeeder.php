<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserInfo;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

class UserSeeder extends Seeder
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
        // SAdmin avatar
        $fileName = '1_1.jpg';
        $image = Image::make(base_path('resources/images/sadmin.jpg'));
        Storage::disk(config('filesystems.public'))->put("avatars/$fileName", (string) $image->stream('jpg'));
        // SAdmin
        $user = User::query()->create([
            'email' => 'sadmin@example.com',
            'email_verified_at' => Carbon::createFromTimestamp(0)->addDay(),
            'password' => Hash::make('AsDf1234'),
            'is_admin' => 1,
            'is_public' => 1,
            'public_family' => 'SAdmin',
            'public_name' => 'SAdmin',
            'public_avatar' => $fileName,
            'created_at' => Carbon::createFromTimestamp(0)->addDay(),
            'updated_at' => Carbon::createFromTimestamp(0)->addDay(),
        ]);
        $user->info()->create([
            'sex' => 0,
            'country' => 'BY',
            'worktype' => 0,
            'work_place' => 'THIS',
        ]);
        $user->secure()->create();

        // Admin1 avatar
        $fileName = '2_2.jpg';
        $image = Image::make(base_path('resources/images/admin1.jpg'));
        Storage::disk(config('filesystems.public'))->put("avatars/$fileName", (string) $image->stream('jpg'));
        // Admin1
        $user = User::query()->create([
            'email' => 'admin1@example.com',
            'email_verified_at' => Carbon::createFromTimestamp(1)->addDay(),
            'password' => Hash::make('AsDf1234'),
            'public_avatar' => $fileName,
            'hiddens' => User::SECURE_FIELDS,
            'created_at' => Carbon::createFromTimestamp(1)->addDay(),
            'updated_at' => Carbon::createFromTimestamp(1)->addDay(),
        ]);
        $user->info()->create([
            'sex' => 0,
            'country' => 'BY',
            'worktype' => 0,
            'work_place' => 'THIS',
        ]);
        $user->secure()->create();
        $user->generatePublicName(true);
        $user->save();

        // Admin2 avatar
        $fileName = '3_3.jpg';
        $image = Image::make(base_path('resources/images/admin2.jpg'));
        Storage::disk(config('filesystems.public'))->put("avatars/$fileName", (string) $image->stream('jpg'));
        // Admin2
        /**
         * @var User $user
         */
        $user = User::query()->create([
            'email' => 'admin2@example.com',
            'email_verified_at' => Carbon::createFromTimestamp(2)->addDay(),
            'password' => Hash::make('AsDf1234'),
            'public_avatar' => $fileName,
            'hiddens' => User::SECURE_FIELDS,
            'created_at' => Carbon::createFromTimestamp(2)->addDay(),
            'updated_at' => Carbon::createFromTimestamp(2)->addDay(),
        ]);
        $user->info()->create([
            'sex' => 1,
            'country' => 'BY',
            'worktype' => 0,
            'work_place' => 'THIS',
        ]);
        $user->secure()->create();
        $user->generatePublicName(true);
        $user->save();

        if (! App::environment('testing')) {
            $this->createUser(50);
        }
    }

    /**
     * @param  int  $count
     *
     * @throws Exception
     */
    private function createUser(int $count = 1): void
    {
        for ($index = 0; $index < $count; $index++) {
            /**
             * @var User $user
             */
            $user = User::factory()->create([
                'public_family' => null,
                'public_name' => null,
                'hiddens' => Arr::random(User::SECURE_FIELDS, random_int(2, 7)),
            ]);
            UserInfo::factory()->create([
                'user_id' => $user->id,
            ]);
            $userInfo = $user->info()->first();

            if (! $userInfo) {
                return;
            }

            $user->secure()->create(['data' => []]);

            $values = $userInfo->toArray();
            unset($values['user_id']);

            $user->saveUserFields($values);
            $user->generatePublicName(true);
            $user->save();
        }
    }
}
