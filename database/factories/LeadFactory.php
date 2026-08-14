<?php

namespace Database\Factories;

use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use App\Modules\Leads\Models\Lead;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lead>
 */
class LeadFactory extends Factory
{
    protected $model = Lead::class;

    public function definition(): array
    {
        return [
            'source' => fake()->randomElement(LeadSource::cases()),
            'status' => LeadStatus::New,
            'name' => fake()->name(),
            'phone' => '(809) '.fake()->numerify('###-####'),
            'email' => fake()->safeEmail(),
            'message' => fake()->sentence(12),
            'ip_address' => fake()->ipv4(),
        ];
    }

    public function contacted(): static
    {
        return $this->state(fn () => [
            'status' => LeadStatus::Contacted,
            'contacted_at' => now(),
        ]);
    }

    public function spam(): static
    {
        return $this->state(fn () => ['status' => LeadStatus::Spam]);
    }
}
