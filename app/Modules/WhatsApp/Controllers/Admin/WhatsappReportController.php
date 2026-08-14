<?php

namespace App\Modules\WhatsApp\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\WhatsApp\Models\WhatsappClick;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WhatsappReportController extends Controller
{
    public function index(Request $request): View
    {
        $request->validate([
            'source' => ['nullable', 'string', 'max:50'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $query = $this->filtered($request);

        return view('admin.whatsapp.index', [
            'clicks' => (clone $query)->with('property.translations')->latest()->paginate(30)->withQueryString(),
            'total' => (clone $query)->count(),
            'today' => (clone $query)->whereDate('created_at', today())->count(),
            'uniqueNumbers' => (clone $query)->distinct()->count('phone_number'),
            'bySource' => (clone $query)->selectRaw('source, COUNT(*) as total')->groupBy('source')->orderByDesc('total')->get(),
            'sources' => WhatsappClick::query()->distinct()->orderBy('source')->pluck('source'),
        ]);
    }

    private function filtered(Request $request): Builder
    {
        return WhatsappClick::query()
            ->when($request->filled('source'), fn ($query) => $query->where('source', (string) $request->string('source')))
            ->when($request->filled('from'), fn ($query) => $query->whereDate('created_at', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($query) => $query->whereDate('created_at', '<=', $request->date('to')));
    }
}
