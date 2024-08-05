<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Organization;
use Exception;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Date;

/**
 * @extends Factory<Organization>
 */
class OrganizationFactory extends Factory
{
    protected $model = Organization::class;

    /**
     * @throws Exception
     */
    public function definition(): array
    {
        $company = $this->faker->company;
        /** @var string $companyName */
        $companyName = array_key_last(array_flip(explode(' ', $company)));
        preg_match_all('/([А-Я])/u', $companyName, $matches);

        $shortName = count($matches[0]) > 2 ? implode('', $matches[0]) : $companyName;

        return [
            'user_id' => random_int(2, 3),
            'type_id' => random_int(1, 3),
            'name' => $company,
            'short_name' => $shortName,
            'description' => $this->faker->realText,
            'site' => "https://{$this->faker->domainName}",
            'email' => "{$this->faker->userName}@{$this->faker->freeEmailDomain}",
            'address' => str_replace("\n", ', ', $this->faker->address),
            'phone' => preg_replace('/\D/', '', $this->faker->phoneNumber),
            'status' => $this->faker->realText(50),
            'registration' => random_int(0, 2),
            'created_at' => $this->faker->dateTimeBetween('-15 days', '-10 days'),
            'updated_at' => $this->faker->dateTimeBetween('-2 days', Date::now()),
        ];
    }
}
