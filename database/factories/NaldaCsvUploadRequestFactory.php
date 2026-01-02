<?php

namespace Database\Factories;

use App\Enums\NaldaCsvUploadStatus;
use App\Models\License;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\NaldaCsvUploadRequest>
 */
class NaldaCsvUploadRequestFactory extends Factory
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
            'sftp_host' => $this->faker->domainName(),
            'sftp_port' => 22,
            'sftp_username' => $this->faker->userName(),
            'sftp_password' => $this->faker->password(),
            'status' => NaldaCsvUploadStatus::Pending,
            'processed_at' => null,
            'error_message' => null,
        ];
    }

    public function processed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => NaldaCsvUploadStatus::Processed,
            'processed_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => NaldaCsvUploadStatus::Failed,
            'processed_at' => now(),
            'error_message' => $this->faker->sentence(),
        ]);
    }
}
