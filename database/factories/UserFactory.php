<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use Exception;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    /**
     * @throws Exception
     */
    public function definition(): array
    {
        $userName = $this->faker->unique()->userName;

        return [
            'referal_id' => random_int(2, User::count()),
            'email' => $userName.'@'.$this->faker->freeEmailDomain,
            'email_verified_at' => $this->faker->dateTimeBetween('-10 days', '-2 days'),
            'password' => Hash::make($this->faker->password),
            'hiddens' => User::SECURE_FIELDS,
            'created_at' => $this->faker->dateTimeBetween('-15 days', '-10 days'),
            'updated_at' => $this->faker->dateTimeBetween('-2 days', Date::now()),
        ];
    }
}
