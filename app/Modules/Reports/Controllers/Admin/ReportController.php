<?php

namespace App\Modules\Reports\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Reports\Requests\ReportRangeRequest;
use App\Modules\Reports\Services\ReportService;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(private ReportService $reports) {}

    public function index(ReportRangeRequest $request): View
    {
        [$desde, $hasta] = $request->range();

        return view('admin.reports.index', [
            'desde' => $desde,
            'hasta' => $hasta,
            'resumen' => $this->reports->summary($desde, $hasta),
            'serie' => $this->reports->dailySeries($desde, $hasta),
            'masVistas' => $this->reports->mostViewed($desde, $hasta),
            'leads' => $this->reports->leadBreakdown($desde, $hasta),
            'whatsapp' => $this->reports->whatsappBreakdown($desde, $hasta),
        ]);
    }

    /**
     * Exportacion CSV de la serie diaria.
     *
     * Va en streaming y no acumulando el fichero en memoria: un rango de dos
     * anos son 730 filas hoy, pero el mismo codigo servira cuando el informe
     * crezca, y una descarga que agota la memoria del servidor cae mal
     * justo cuando mas datos hay.
     */
    public function export(ReportRangeRequest $request): StreamedResponse
    {
        [$desde, $hasta] = $request->range();

        $nombre = "era-realty-reporte-{$desde->format('Y-m-d')}_{$hasta->format('Y-m-d')}.csv";

        return response()->streamDownload(function () use ($desde, $hasta) {
            $salida = fopen('php://output', 'w');

            // BOM UTF-8: sin el, Excel en Windows abre el CSV en ANSI y los
            // acentos salen como simbolos. Es el primer reproche que llega
            // cuando alguien abre el fichero en la oficina.
            fwrite($salida, "\xEF\xBB\xBF");

            fputcsv($salida, ['Fecha', 'Visitas', 'Clics de WhatsApp', 'Leads'], ';');

            foreach ($this->reports->exportRows($desde, $hasta) as $fila) {
                fputcsv($salida, array_values($fila), ';');
            }

            fclose($salida);
        }, $nombre, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
