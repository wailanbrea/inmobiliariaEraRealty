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
            // ---------------- Sobre nosotros ----------------
            [
                'page_key' => 'about',
                'section_key' => 'hero',
                'sort_order' => 0,
                'translations' => [
                    'es' => [
                        'title' => 'Sobre nosotros',
                        'subtitle' => 'Acompañamos a quien compra, vende o invierte en República Dominicana.',
                    ],
                    'en' => [
                        'title' => 'About us',
                        'subtitle' => 'We guide people buying, selling or investing in the Dominican Republic.',
                    ],
                ],
            ],
            [
                'page_key' => 'about',
                'section_key' => 'story',
                'sort_order' => 1,
                'translations' => [
                    'es' => [
                        'title' => 'Quiénes somos',
                        'content' => "Somos un equipo de asesores inmobiliarios en República Dominicana. Trabajamos con propiedades verificadas y acompañamos cada operación de principio a fin: desde la primera visita hasta la firma y el traspaso.\n\nNos mueve una idea sencilla: comprar una propiedad es una de las decisiones más grandes que toma una persona, y merece información clara y honesta, no presión comercial.",
                    ],
                    'en' => [
                        'title' => 'Who we are',
                        'content' => "We are a team of real estate advisers in the Dominican Republic. We work with verified listings and support every transaction from start to finish: from the first viewing through signing and transfer.\n\nOne simple idea drives us: buying a property is one of the biggest decisions a person makes, and it deserves clear, honest information rather than sales pressure.",
                    ],
                ],
            ],
            [
                'page_key' => 'about',
                'section_key' => 'values',
                'sort_order' => 2,
                'extra_json' => [
                    [
                        'icon' => 'verified',
                        'title_es' => 'Propiedades verificadas',
                        'title_en' => 'Verified listings',
                        'text_es' => 'Comprobamos títulos y situación legal antes de publicar. Si algo no cuadra, no sale en la web.',
                        'text_en' => 'We check title and legal status before publishing. If something does not add up, it does not go online.',
                    ],
                    [
                        'icon' => 'handshake',
                        'title_es' => 'Sin presión comercial',
                        'title_en' => 'No sales pressure',
                        'text_es' => 'Te decimos también lo que no encaja contigo. Una venta forzada no construye una relación.',
                        'text_en' => 'We also tell you what does not suit you. A forced sale does not build a relationship.',
                    ],
                    [
                        'icon' => 'translate',
                        'title_es' => 'Atención en dos idiomas',
                        'title_en' => 'Service in two languages',
                        'text_es' => 'Atendemos en español e inglés, presencialmente o a distancia si estás fuera del país.',
                        'text_en' => 'We work in Spanish and English, in person or remotely if you are abroad.',
                    ],
                ],
                'translations' => [
                    'es' => [
                        'title' => 'Cómo trabajamos',
                        'subtitle' => 'Tres compromisos que sostienen todo lo demás.',
                    ],
                    'en' => [
                        'title' => 'How we work',
                        'subtitle' => 'Three commitments that hold up everything else.',
                    ],
                ],
            ],
            [
                'page_key' => 'about',
                'section_key' => 'team',
                'sort_order' => 3,
                'translations' => [
                    'es' => [
                        'title' => 'El equipo',
                        'subtitle' => 'Las personas que te acompañan.',
                    ],
                    'en' => [
                        'title' => 'The team',
                        'subtitle' => 'The people who work with you.',
                    ],
                ],
            ],
            [
                'page_key' => 'about',
                'section_key' => 'cta',
                'sort_order' => 4,
                'translations' => [
                    'es' => [
                        'title' => '¿Empezamos?',
                        'subtitle' => 'Cuéntanos qué buscas y te decimos con franqueza si podemos ayudarte.',
                        'button_text' => 'Contáctanos',
                    ],
                    'en' => [
                        'title' => 'Shall we start?',
                        'subtitle' => 'Tell us what you are looking for and we will be straight about whether we can help.',
                        'button_text' => 'Contact us',
                    ],
                ],
            ],

            // ---------------- Invierte ----------------
            [
                'page_key' => 'invest',
                'section_key' => 'hero',
                'sort_order' => 0,
                'translations' => [
                    'es' => [
                        'title' => 'Invierte en República Dominicana',
                        'subtitle' => 'El Caribe con la economía de mayor crecimiento de la región, y una de las pocas donde el extranjero compra en igualdad de condiciones.',
                    ],
                    'en' => [
                        'title' => 'Invest in the Dominican Republic',
                        'subtitle' => 'The fastest-growing economy in the Caribbean, and one of the few where foreign buyers have the same rights as locals.',
                    ],
                ],
            ],
            [
                'page_key' => 'invest',
                'section_key' => 'why_invest',
                'sort_order' => 1,
                'extra_json' => [
                    [
                        'icon' => 'public',
                        'title_es' => 'Sin restricciones para extranjeros',
                        'title_en' => 'No restrictions for foreigners',
                        'text_es' => 'La ley dominicana equipara al comprador extranjero con el nacional. No hace falta residencia ni socio local.',
                        'text_en' => 'Dominican law gives foreign buyers the same rights as nationals. No residency or local partner required.',
                    ],
                    [
                        'icon' => 'trending_up',
                        'title_es' => 'Turismo en crecimiento sostenido',
                        'title_en' => 'Sustained tourism growth',
                        'text_es' => 'Punta Cana recibe millones de visitantes al año, lo que sostiene la demanda de alquiler vacacional.',
                        'text_en' => 'Punta Cana welcomes millions of visitors a year, sustaining vacation rental demand.',
                    ],
                    [
                        'icon' => 'savings',
                        'title_es' => 'Incentivos fiscales',
                        'title_en' => 'Tax incentives',
                        'text_es' => 'La Ley CONFOTUR exime de ciertos impuestos a proyectos turísticos aprobados. Conviene verificarlo proyecto por proyecto.',
                        'text_en' => 'The CONFOTUR law exempts approved tourism projects from certain taxes. Worth verifying project by project.',
                    ],
                    [
                        'icon' => 'payments',
                        'title_es' => 'Operación en dólares',
                        'title_en' => 'Deals in US dollars',
                        'text_es' => 'Buena parte del mercado de lujo opera en dólares, lo que reduce la exposición al tipo de cambio.',
                        'text_en' => 'Much of the luxury market trades in US dollars, reducing exchange-rate exposure.',
                    ],
                ],
                'translations' => [
                    'es' => [
                        'title' => '¿Por qué República Dominicana?',
                        'subtitle' => 'Cuatro razones que explican el interés del inversionista extranjero.',
                    ],
                    'en' => [
                        'title' => 'Why the Dominican Republic?',
                        'subtitle' => 'Four reasons behind the interest from foreign investors.',
                    ],
                ],
            ],
            [
                'page_key' => 'invest',
                'section_key' => 'process',
                'sort_order' => 2,
                'extra_json' => [
                    [
                        'title_es' => 'Definimos tu objetivo',
                        'title_en' => 'We define your goal',
                        'text_es' => 'Renta vacacional, plusvalía a medio plazo o segunda residencia: cada objetivo lleva a una zona y a un tipo de propiedad distintos.',
                        'text_en' => 'Vacation income, medium-term appreciation or a second home: each goal points to a different area and property type.',
                    ],
                    [
                        'title_es' => 'Selección y visita',
                        'title_en' => 'Shortlist and viewing',
                        'text_es' => 'Te preparamos una selección corta y organizamos las visitas, presenciales o por video si estás fuera del país.',
                        'text_en' => 'We prepare a short list and arrange viewings, in person or by video if you are abroad.',
                    ],
                    [
                        'title_es' => 'Debida diligencia',
                        'title_en' => 'Due diligence',
                        'text_es' => 'Verificación de títulos, cargas y situación legal con un abogado independiente antes de cualquier pago.',
                        'text_en' => 'Title, liens and legal status verified by an independent lawyer before any payment.',
                    ],
                    [
                        'title_es' => 'Cierre y acompañamiento',
                        'title_en' => 'Closing and follow-up',
                        'text_es' => 'Acompañamos la firma y el traspaso, y te conectamos con administración si vas a alquilar.',
                        'text_en' => 'We support signing and transfer, and connect you with property management if you plan to rent.',
                    ],
                ],
                'translations' => [
                    'es' => [
                        'title' => 'Cómo trabajamos',
                        'subtitle' => 'Cuatro pasos, sin sorpresas.',
                    ],
                    'en' => [
                        'title' => 'How we work',
                        'subtitle' => 'Four steps, no surprises.',
                    ],
                ],
            ],
            [
                'page_key' => 'invest',
                'section_key' => 'disclaimer',
                'sort_order' => 3,
                'translations' => [
                    'es' => [
                        'content' => 'La información de esta página es orientativa y no constituye asesoría legal, fiscal ni de inversión. Toda operación debe verificarse con un abogado y un asesor fiscal independientes antes de comprometer fondos.',
                    ],
                    'en' => [
                        'content' => 'The information on this page is indicative and does not constitute legal, tax or investment advice. Every transaction should be verified with an independent lawyer and tax adviser before committing funds.',
                    ],
                ],
            ],
            [
                'page_key' => 'invest',
                'section_key' => 'cta',
                'sort_order' => 4,
                'translations' => [
                    'es' => [
                        'title' => '¿Hablamos de tu inversión?',
                        'subtitle' => 'Cuéntanos qué buscas y te preparamos una selección a medida.',
                        'button_text' => 'Contáctanos',
                    ],
                    'en' => [
                        'title' => 'Shall we talk about your investment?',
                        'subtitle' => 'Tell us what you are looking for and we will put together a tailored selection.',
                        'button_text' => 'Contact us',
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
