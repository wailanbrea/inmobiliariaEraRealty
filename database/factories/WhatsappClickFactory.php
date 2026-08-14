<?php

namespace Database\Factories;

use App\Modules\WhatsApp\Models\WhatsappClick;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WhatsappClick>
 */
class WhatsappClickFactory extends Factory
{
    protected $model = WhatsappClick::class;

    public function definition(): array
    {
        return [
            // Los mismos origenes que deduce resources/js/app.js.
            'source' => fake()->randomElement([
                'website', 'property_detail', 'investment_page', 'contact_page', 'about_page',
            ]),
            'phone_number' => '1809'.fake()->numerify('#######'),
            'generated_message' => fake()->sentence(8),
            'ip_address' => fake()->ipv4(),
        ];
    }
}
