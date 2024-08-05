<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

class CreateSAdminCommand extends Command
{
    protected $signature = 'create:sadmin {email} {password?}';

    protected $description = 'Create SAdmin';

    public function handle(): void
    {
        $email = $this->argument('email');
        $password = $this->argument('password');

        if (! $password) {
            $password = Str::random();
        }

        if (is_array($email)) {
            $email = array_shift($email);
        }

        if (is_array($password)) {
            $password = array_shift($password);
        }

        /**
         * @var User $user
         */
        $user = User::query()->create([
            'email' => $email,
            'password' => Hash::make((string) $password),
            'is_public' => 1,
        ]);

        $user->is_admin = true;

        // SAdmin avatar
        $fileName = $user->id.'_1.jpg';

        if (file_exists(base_path('resources/images/sadmin.jpg'))) {
            $image = Image::make(base_path('resources/images/sadmin.jpg'));
            Storage::disk(config('filesystems.public'))->put("avatars/$fileName", (string) $image->stream('jpg'));
            $user->public_avatar = $fileName;
        }

        $user->save();
        $user->markEmailAsVerified();

        $user->saveUserFields([
            'family' => 'SAdmin',
            'name' => 'SAdmin',
            'patronymic' => 'SAdmin',
            'sex' => 0,
            'birthday' => Carbon::now(),
            'country' => 'BY',
            'worktype' => 0,
            'scope' => 9,
            'work_place' => 'THIS',
            'work_position' => 'THIS',
            'address' => 'Somewhere',
            'phone' => '1',
        ]);

        $this->comment("SAdmin created successfully!\nEmail: $email\nPassword: $password");
    }
}
