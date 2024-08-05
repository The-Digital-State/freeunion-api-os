<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\InviteLink;
use Exception;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Date;

/**
 * @extends Factory<InviteLink>
 */
class InviteLinkFactory extends Factory
{
    protected $model = InviteLink::class;

    /**
     * @throws Exception
     */
    public function definition(): array
    {
        return [
            'user_id' => random_int(4, 50),
            'created_at' => $this->faker->dateTimeBetween('-10 days', Date::now()),
        ];
    }
}
