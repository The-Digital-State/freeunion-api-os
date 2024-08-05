<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Storage;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run(): void
    {
        Storage::disk(config('filesystems.public'))->delete(array_filter(
            Storage::disk(config('filesystems.public'))->allFiles(),
            static function ($item) {
                return $item !== '.gitignore';
            }
        ));
        Storage::disk(config('filesystems.private'))->delete(array_filter(
            Storage::disk(config('filesystems.private'))->allFiles(),
            static function ($item) {
                return $item !== '.gitignore';
            }
        ));

        $this->call(DictionarySeeder::class);

        $this->call(UserSeeder::class);
        $this->call(InviteLinkSeeder::class);

        if (! App::environment('testing')) {
            $this->call(OrganizationSeeder::class);
            $this->call(SuggestionSeeder::class);
        }
    }
}
