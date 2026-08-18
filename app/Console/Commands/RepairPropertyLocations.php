<?php

namespace App\Console\Commands;

use App\Modules\Locations\Models\City;
use App\Modules\Locations\Models\Province;
use App\Modules\Locations\Models\Sector;
use App\Modules\Properties\Models\Property;
use Illuminate\Console\Command;

class RepairPropertyLocations extends Command
{
    protected $signature = 'properties:repair-locations
                            {--dry-run : Mostrar cambios sin guardarlos}
                            {--rollback : Revertir las asignaciones realizadas por este comando}';

    protected $description = 'Completa ubicaciones vacias usando titulos y direcciones claros';

    public function handle(): int
    {
        if ($this->option('rollback')) {
            return $this->rollback();
        }

        $provinces = Province::active()->get();
        $cities = City::active()->with('province')->get();
        $sectors = Sector::active()->with('city.province')->get();
        $changed = 0;

        foreach (Property::withTrashed()->with('translations')->get() as $property) {
            $text = trim(($property->address ?? '').' '.$property->translations->pluck('title')->implode(' '));
            $cityMatches = $this->matches($cities, $text);
            $provinceMatches = $this->matches($provinces, $text);
            $sectorMatches = $this->matches($sectors, $text);

            $city = $this->resolveCity($property, $cities, $cityMatches);
            $sector = $this->resolveSector($property, $sectorMatches, $city);
            $province = $this->resolveProvince($property, $provinces, $provinceMatches, $city, $sector, $text);

            $data = [];
            if (! $property->province_id && $province) {
                $data['province_id'] = $province->id;
            }
            if (! $property->city_id && $city) {
                $data['city_id'] = $city->id;
            }
            if (! $property->sector_id && $sector) {
                $data['sector_id'] = $sector->id;
            }

            if ($data === []) {
                continue;
            }

            $changed++;
            $this->line($property->reference_code.' '.json_encode($data));

            if (! $this->option('dry-run')) {
                $property->update($data);
            }
        }

        $this->info(($this->option('dry-run') ? 'Detectados: ' : 'Actualizados: ').$changed);

        return self::SUCCESS;
    }

    private function rollback(): int
    {
        $fieldsByReference = [
            '169-T' => ['province_id', 'city_id'],
            '486-A' => ['province_id', 'sector_id'],
            '497-V' => ['province_id', 'city_id'],
            '544-A' => ['sector_id'],
            '556-A' => ['city_id', 'sector_id'],
            '572-S' => ['city_id', 'sector_id'],
            '588-T' => ['city_id', 'sector_id'],
            '612-S' => ['city_id'],
            '617-F' => ['province_id', 'city_id'],
            '620-S' => ['province_id', 'city_id'],
            '625-S' => ['province_id', 'city_id'],
            '630-A' => ['province_id', 'city_id'],
            '633-S' => ['province_id', 'city_id'],
            '640-S' => ['sector_id'],
            '642-P' => ['province_id', 'sector_id'],
            '645-S' => ['province_id', 'city_id'],
            '646-A-3792' => ['sector_id'],
            '648-A' => ['province_id', 'sector_id'],
            '651-A' => ['province_id', 'sector_id'],
            '652-A' => ['province_id', 'sector_id'],
            '653-S' => ['city_id'],
            '655-V' => ['province_id', 'city_id'],
            '657-A' => ['province_id', 'sector_id'],
            '660-A' => ['province_id', 'sector_id'],
            '661-T' => ['city_id'],
            '663-A' => ['sector_id'],
            '665-S' => ['province_id', 'city_id'],
            '666-A' => ['sector_id'],
            '667-S' => ['province_id', 'city_id', 'sector_id'],
            '670-T' => ['province_id', 'sector_id'],
            '671-A' => ['province_id', 'city_id'],
            '672-S' => ['province_id', 'city_id', 'sector_id'],
            '674-S' => ['city_id', 'sector_id'],
            '675-A' => ['province_id', 'sector_id'],
            '676-V' => ['province_id'],
            '680-S' => ['province_id', 'city_id', 'sector_id'],
            '681-A' => ['province_id', 'sector_id'],
            '682-T' => ['city_id', 'sector_id'],
            '683-P' => ['province_id', 'sector_id'],
            '684-A' => ['province_id', 'sector_id'],
            '685-P' => ['province_id', 'sector_id'],
            '686-A' => ['province_id', 'sector_id'],
            '687-V' => ['province_id', 'city_id'],
            '688-V' => ['province_id', 'city_id'],
            '693-A' => ['province_id', 'city_id'],
            '694-V' => ['province_id', 'city_id', 'sector_id'],
            '699-V' => ['province_id', 'city_id'],
            '701-P' => ['province_id', 'sector_id'],
            '702-V' => ['province_id', 'city_id'],
            '704-A' => ['province_id', 'sector_id'],
            '705-P' => ['province_id', 'sector_id'],
            '708-A' => ['city_id'],
            '709-V' => ['province_id', 'city_id'],
            '712-T' => ['province_id', 'sector_id'],
            '713-F' => ['province_id', 'city_id', 'sector_id'],
            '715-T' => ['province_id', 'sector_id'],
            '716-A' => ['sector_id'],
            '717-A' => ['province_id', 'sector_id'],
            '718-A' => ['province_id', 'sector_id'],
            '719-A-4965' => ['province_id', 'city_id'],
            '723-S' => ['province_id', 'city_id'],
            '725-S' => ['province_id', 'city_id'],
            '728-A' => ['province_id', 'sector_id'],
            '729-A' => ['sector_id'],
            '731-A' => ['province_id', 'sector_id'],
            '735-V' => ['city_id', 'sector_id'],
            'ERA-9001' => ['sector_id'],
            'ERA-9003' => ['sector_id'],
            'LA-JA-628-V' => ['city_id'],
            'LA-JA-635-T' => ['city_id'],
            'LA-JA-639-T' => ['city_id'],
            'SD-DN-634-L' => ['sector_id'],
            'SE-SE-621-A' => ['sector_id'],
            'SO-SO-638-C' => ['sector_id'],
            'WP-1951' => ['province_id', 'sector_id'],
            'WP-3135' => ['sector_id'],
            'WP-3742' => ['sector_id'],
            'ERA-9007' => ['sector_id'],
        ];

        foreach ($fieldsByReference as $reference => $fields) {
            $property = Property::withTrashed()->where('reference_code', $reference)->first();

            if (! $property) {
                continue;
            }

            $property->update(array_fill_keys($fields, null));
        }

        $this->info('Se revirtieron '.count($fieldsByReference).' propiedades.');

        return self::SUCCESS;
    }

    private function matches(iterable $items, string $text): array
    {
        $matches = [];

        foreach ($items as $item) {
            $name = trim($item->name);

            if (mb_strlen($name) >= 5 && mb_stripos($text, $name) !== false) {
                $matches[] = $item;
            }
        }

        return $matches;
    }

    private function resolveCity(Property $property, $cities, array $matches): ?City
    {
        if ($property->city_id) {
            return $cities->firstWhere('id', $property->city_id);
        }

        if ($property->province_id) {
            $matches = array_values(array_filter($matches, fn (City $city) => $city->province_id === $property->province_id));
        }

        return count($matches) === 1 ? $matches[0] : null;
    }

    private function resolveSector(Property $property, array $matches, ?City $city): ?Sector
    {
        if ($property->sector_id) {
            return null;
        }

        if ($city) {
            $matches = array_values(array_filter($matches, fn (Sector $sector) => $sector->city_id === $city->id));
        }

        return count($matches) === 1 ? $matches[0] : null;
    }

    private function resolveProvince(
        Property $property,
        $provinces,
        array $matches,
        ?City $city,
        ?Sector $sector,
        string $text,
    ): ?Province {
        if ($property->province_id) {
            return $provinces->firstWhere('id', $property->province_id);
        }

        if ($city?->province_id) {
            return $provinces->firstWhere('id', $city->province_id);
        }

        if ($sector?->city?->province_id) {
            return $provinces->firstWhere('id', $sector->city->province_id);
        }

        if (count($matches) === 1) {
            return $matches[0];
        }

        return mb_stripos($text, 'Distrito Nacional') !== false
            ? $provinces->firstWhere('slug', 'santo-domingo')
            : null;
    }
}
