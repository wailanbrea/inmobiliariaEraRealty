<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

/**
 * Datos estructurados de alcance global.
 *
 * Los de cada pagina (RealEstateListing, NewsArticle) viven en su propia
 * vista, porque dependen del modelo. Lo que hay aqui es lo que no cambia:
 * quien es la empresa.
 */
class Seo
{
    /**
     * Ficha de la organizacion en schema.org.
     *
     * Se usa RealEstateAgent y no Organization a secas: es el tipo especifico
     * que Google reconoce para inmobiliarias, y habilita el panel lateral con
     * telefono, direccion y horario.
     *
     * @return array<string, mixed>
     */
    public static function organization(): array
    {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'RealEstateAgent',
            '@id' => url('/').'#organization',
            'name' => setting('site_name') ?: config('app.name'),
            'url' => url('/'),
        ];

        if ($descripcion = setting('seo_default_description')) {
            $schema['description'] = $descripcion;
        }

        if ($logo = setting('site_logo')) {
            $schema['logo'] = url(Storage::url($logo));
            $schema['image'] = $schema['logo'];
        }

        if ($telefono = setting('contact_phone')) {
            $schema['telephone'] = $telefono;
        }

        if ($correo = setting('contact_email')) {
            $schema['email'] = $correo;
        }

        if ($direccion = setting('contact_address')) {
            $schema['address'] = [
                '@type' => 'PostalAddress',
                'streetAddress' => $direccion,
                'addressCountry' => 'DO',
            ];
        }

        // sameAs enlaza los perfiles oficiales. Es lo que le permite a Google
        // saber que esas cuentas son de la misma empresa y no homonimas.
        $redes = collect([
            'social_facebook', 'social_instagram', 'social_youtube',
            'social_tiktok', 'social_linkedin',
        ])->map(fn ($clave) => setting($clave))->filter()->values()->all();

        if ($redes) {
            $schema['sameAs'] = $redes;
        }

        if ($horario = setting('contact_schedule')) {
            $schema['openingHours'] = $horario;
        }

        // El idioma se declara para que el rastreador no tenga que inferirlo
        // del prefijo de la URL.
        $schema['availableLanguage'] = array_map(
            fn ($code) => Locale::meta($code)['html_lang'] ?? $code,
            Locale::codes()
        );

        return $schema;
    }
}
