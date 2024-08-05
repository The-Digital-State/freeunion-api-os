<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Suggestion;
use Exception;
use Illuminate\Database\Seeder;

class SuggestionSeeder extends Seeder
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
        Suggestion::factory()->count(100)->create();
    }
}
