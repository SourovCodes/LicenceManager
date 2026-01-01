<?php

namespace Database\Factories;

use App\Enums\LicenseStatus;
use App\Models\License;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\License>
 */
class LicenseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $activatedAt = $this->faker->optional(0.7)->dateTimeBetween('-1 year', 'now');
        $validityDays = $this->faker->randomElement([30, 90, 180, 365]);

        return [
            'product_id' => Product::factory(),
            'license_key' => License::generateLicenseKey(),
            'customer_name' => $this->faker->name(),
            'customer_email' => $this->faker->safeEmail(),
            'status' => $this->faker->randomElement(LicenseStatus::cases()),
            'validity_days' => $validityDays,
            'activated_at' => $activatedAt,
            'expires_at' => $activatedAt ? (clone $activatedAt)->modify("+{$validityDays} days") : null,
            'max_domain_changes' => $this->faker->numberBetween(1, 5),
            'domain_changes_used' => $this->faker->numberBetween(0, 2),
            'notes' => $this->faker->optional(0.3)->sentence(),
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => LicenseStatus::Active,
            'activated_at' => now()->subDays(30),
            'expires_at' => now()->addDays(max(30, $attributes['validity_days'] - 30)),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => LicenseStatus::Expired,
            'activated_at' => now()->subDays($attributes['validity_days'] + 30),
            'expires_at' => now()->subDays(30),
        ]);
    }

    public function revoked(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => LicenseStatus::Revoked,
        ]);
    }

    public function unactivated(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => LicenseStatus::Active,
            'activated_at' => null,
            'expires_at' => null,
        ]);
    }
}
