<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UserActions>
 */
class UserActionsFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'action' => 'submit_form',
            'rules' => [
                [
                    'field' => 'role',
                    'operator' => '==',
                    'value' => 'staff',
                ],
                [
                    'field' => 'email_verified_at',
                    'operator' => '!=',
                    'value' => null,
                ],
            ],
        ];
    }
}
