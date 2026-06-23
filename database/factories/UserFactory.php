<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name'          => fake()->name(),
            'email'         => fake()->unique()->safeEmail(),
            'password'      => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'active_state'  => 1,
            'company'       => fake()->company(),
            'photo'         => 'http://erp.antbd.net/assets/images/user_photo/defult.jpg',
        ];
    }
}
