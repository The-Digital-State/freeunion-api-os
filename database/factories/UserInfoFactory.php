<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\UserInfo;
use Exception;
use Faker\Provider\ru_RU\Person;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserInfo>
 */
class UserInfoFactory extends Factory
{
    protected $model = UserInfo::class;

    /**
     * @throws Exception
     */
    public function definition(): array
    {
        $person = new Person($this->faker);
        $sex = random_int(0, 1);

        return [
            'family' => $person->lastName($sex === 0 ? 'male' : 'female'),
            'name' => $person->firstName($sex === 0 ? 'male' : 'female'),
            'patronymic' => $person->middleName($sex === 0 ? 'male' : 'female'),
            'sex' => $sex,
            'birthday' => $this->faker->dateTimeBetween('-50 years', '-20 years'),
            'country' => 'BY',
            'worktype' => 2,
            'scope' => random_int(1, 17),
            'work_place' => $this->faker->company,
            'work_position' => $this->faker->jobTitle,
            'address' => str_replace("\n", ', ', $this->faker->address),
            'phone' => preg_replace('/\D/', '', $this->faker->phoneNumber),
            'about' => $this->faker->realText(100),
        ];
    }
}
