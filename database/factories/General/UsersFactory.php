<?php

namespace Database\Factories\General;

use App\Models\General\Users;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class UsersFactory extends Factory
{
    protected $model = Users::class;

    public function definition(): array
    {
        return [
            'dep_code' => 'DEP-001',
            'name' => $this->faker->name,
            'username' => $this->faker->userName,
            'password' => Hash::make('123'),
            'role_id' => $this->faker->numberBetween(1, 5),
        ];
    }
}
