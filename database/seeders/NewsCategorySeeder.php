<?php

namespace Database\Seeders;

use App\Modules\News\Models\NewsCategory;
use Illuminate\Database\Seeder;

class NewsCategorySeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['mercado', 'Mercado', 'Market', '#0058BE'],
            ['inversion', 'Inversion', 'Investment', '#009668'],
            ['guias', 'Guias', 'Guides', '#7C3AED'],
            ['noticias', 'Noticias', 'News', '#D97706'],
        ] as $index => [$slug, $es, $en, $color]) {
            NewsCategory::updateOrCreate(['slug' => $slug], [
                'name' => ['es' => $es, 'en' => $en],
                'color' => $color,
                'is_active' => true,
                'sort_order' => $index,
            ]);
        }
    }
}
