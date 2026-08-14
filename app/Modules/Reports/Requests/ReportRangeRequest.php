<?php

namespace App\Modules\Reports\Requests;

use App\Modules\Reports\Services\ReportService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

class ReportRangeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['admin', 'super_admin']) ?? false;
    }

    public function rules(): array
    {
        return [
            'desde' => ['nullable', 'date'],
            // El limite superior es 'hoy': un rango que termina en el futuro
            // solo puede devolver ceros y hace creer que algo se rompio.
            'hasta' => ['nullable', 'date', 'after_or_equal:desde', 'before_or_equal:today'],
        ];
    }

    /**
     * Rango saneado y siempre coherente.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    public function range(): array
    {
        [$pordefecto, $hoy] = ReportService::defaultRange();

        $desde = $this->filled('desde') ? Carbon::parse($this->input('desde'))->startOfDay() : $pordefecto;
        $hasta = $this->filled('hasta') ? Carbon::parse($this->input('hasta'))->endOfDay() : $hoy;

        // Ultima red: si por lo que sea llegaran invertidas, se ordenan en vez
        // de devolver un informe vacio que parece un fallo del sistema.
        return $desde->lte($hasta) ? [$desde, $hasta] : [$hasta->startOfDay(), $desde->endOfDay()];
    }
}
