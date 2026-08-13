<?php

namespace Database\Seeders;

use App\Modules\Pages\Models\ContentSection;
use Illuminate\Database\Seeder;

/**
 * Bloques editables del inicio.
 *
 * Los textos salen del diseno de Stitch, pero aqui viven en base de datos:
 * el cliente puede cambiarlos sin tocar codigo, que es la exigencia del
 * prompt maestro.
 */
class ContentSectionSeeder extends Seeder
{
    /** @return list<array<string, mixed>> */
    public static function definitions(): array
    {
        return [
            [
                'page_key' => 'home',
                'section_key' => 'hero',
                'sort_order' => 0,
                'translations' => [
                    'es' => [
                        'title' => 'Encuentra tu próxima propiedad en República Dominicana',
                        'subtitle' => 'Compra, alquila o invierte con asesoría profesional y propiedades verificadas.',
                    ],
                    'en' => [
                        'title' => 'Find your next property in the Dominican Republic',
                        'subtitle' => 'Buy, rent or invest with professional guidance and verified listings.',
                    ],
                ],
            ],
            [
                'page_key' => 'home',
                'section_key' => 'featured_properties',
                'sort_order' => 1,
                'translations' => [
                    'es' => [
                        'title' => 'Propiedades Destacadas',
                        'subtitle' => 'Selección exclusiva de las mejores oportunidades del mercado.',
                    ],
                    'en' => [
                        'title' => 'Featured Properties',
                        'subtitle' => 'An exclusive selection of the best opportunities on the market.',
                    ],
                ],
            ],
            [
                'page_key' => 'home',
                'section_key' => 'stats',
                'sort_order' => 2,
                'extra_json' => [
                    ['value' => '250', 'suffix' => '+', 'label_es' => 'Propiedades vendidas', 'label_en' => 'Properties sold'],
                    ['value' => '15', 'suffix' => '', 'label_es' => 'Años de experiencia', 'label_en' => 'Years of experience'],
                    ['value' => '500', 'suffix' => '+', 'label_es' => 'Clientes satisfechos', 'label_en' => 'Happy clients'],
                    ['value' => '32', 'suffix' => '', 'label_es' => 'Provincias cubiertas', 'label_en' => 'Provinces covered'],
                ],
                'translations' => [
                    'es' => ['title' => 'En cifras'],
                    'en' => ['title' => 'By the numbers'],
                ],
            ],
            [
                'page_key' => 'home',
                'section_key' => 'investment_cta',
                'sort_order' => 3,
                'translations' => [
                    'es' => [
                        'title' => 'Oportunidades de inversión',
                        'subtitle' => 'Propiedades seleccionadas por su potencial de rentabilidad.',
                        'button_text' => 'Ver todas las oportunidades',
                    ],
                    'en' => [
                        'title' => 'Investment opportunities',
                        'subtitle' => 'Properties selected for their return potential.',
                        'button_text' => 'View all opportunities',
                    ],
                ],
            ],
            [
                'page_key' => 'home',
                'section_key' => 'final_cta',
                'sort_order' => 4,
                'translations' => [
                    'es' => [
                        'title' => '¿Hablamos de tu próxima propiedad?',
                        'subtitle' => 'Cuéntanos qué buscas y te acompañamos en todo el proceso.',
                        'button_text' => 'Contáctanos',
                    ],
                    'en' => [
                        'title' => 'Shall we talk about your next property?',
                        'subtitle' => 'Tell us what you are looking for and we will guide you through the process.',
                        'button_text' => 'Contact us',
                    ],
                ],
            ],
        ];
    }

    public function run(): void
    {
        foreach (self::definitions() as $definicion) {
            $seccion = ContentSection::updateOrCreate(
                [
                    'page_key' => $definicion['page_key'],
                    'section_key' => $definicion['section_key'],
                ],
                [
                    'sort_order' => $definicion['sort_order'],
                    'extra_json' => $definicion['extra_json'] ?? null,
                    'is_active' => true,
                ],
            );

            foreach ($definicion['translations'] as $locale => $campos) {
                // firstOrCreate, no update: si el cliente ya cambio el texto,
                // no se le pisa al volver a sembrar.
                $seccion->translations()->firstOrCreate(
                    ['locale' => $locale],
                    $campos,
                );
            }
        }

        ContentSection::flushCache('home');

        $this->command->info('Secciones de contenido: '.count(self::definitions()).'.');
    }
}
