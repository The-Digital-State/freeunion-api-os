<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Organization;
use App\Models\Suggestion;
use App\Models\User;
use Exception;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Date;

/**
 * @extends Factory<Suggestion>
 */
class SuggestionFactory extends Factory
{
    protected $model = Suggestion::class;

    /**
     * @throws Exception
     */
    public function definition(): array
    {
        /** @var Organization $organization */
        $organization = Organization::find(random_int(1, Organization::count()));
        /** @var User $user */
        $user = $organization->members->random();

        return [
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'title' => $this->faker->realText(40),
            'description' => $this->faker->realText,
            'created_at' => $this->faker->dateTimeBetween('-5 days', '-2 days'),
            'updated_at' => $this->faker->dateTimeBetween('-2 days', Date::now()),
        ];
    }
}
