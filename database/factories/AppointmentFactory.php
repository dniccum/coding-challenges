<?php

namespace Database\Factories;

use App\Enums\Status;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Appointment>
 */
class AppointmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $date = fake()->dateTimeBetween('-1 month', '+2 months');
        $start = Carbon::instance($date)
            ->setTime(hour: fake()->numberBetween(8, 16), minute: [0, 15, 30, 45][fake()->numberBetween(0, 3)], second: 0);

        $end = (clone $start)->addMinutes([30, 45, 60, 90, 120][fake()->numberBetween(0, 4)]);

        return [
            'patient_id' => Patient::factory(),
            'date' => $start->toDateString(),
            'start_time' => $start->format('H:i:s'),
            'end_time' => $end->format('H:i:s'),
            'status' => fake()->boolean(70) ? Status::Confirmed : Status::Unconfirmed,
        ];
    }

    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => ['status' => Status::Confirmed]);
    }

    public function unconfirmed(): static
    {
        return $this->state(fn (array $attributes) => ['status' => Status::Unconfirmed]);
    }
}
