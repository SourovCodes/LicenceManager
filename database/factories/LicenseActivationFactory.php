<?php

namespace Database\Factories;

use App\Models\License;
use App\Models\LicenseActivation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LicenseActivation>
 */
class LicenseActivationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'license_id' => License::factory(),
            'domain' => $this->faker->domainName(),
            'ip_address' => $this->faker->ipv4(),
            'local_key' => LicenseActivation::generateLocalKey(),
            'is_active' => true,
            'activated_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'deactivated_at' => null,
            'deactivation_reason' => null,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
            'deactivated_at' => null,
            'deactivation_reason' => null,
        ]);
    }

    public function deactivated(?string $reason = null): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
            'deactivated_at' => now(),
            'deactivation_reason' => $reason ?? $this->faker->sentence(),
        ]);
    }
}
