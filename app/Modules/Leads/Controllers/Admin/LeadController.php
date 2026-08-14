<?php

namespace App\Modules\Leads\Controllers\Admin;

use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Leads\Models\Lead;
use App\Modules\Leads\Requests\UpdateLeadRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LeadController extends Controller
{
    public function index(Request $request): View
    {
        return view('admin.leads.index', [
            'leads' => $this->filtered($request)->with(['property.translations', 'assignee'])->latest()->paginate(25)->withQueryString(),
            'statuses' => LeadStatus::cases(),
            'sources' => LeadSource::cases(),
        ]);
    }

    public function show(Lead $lead): View
    {
        return view('admin.leads.show', [
            'lead' => $lead->load(['property.translations', 'assignee']),
            'statuses' => LeadStatus::cases(),
            'users' => User::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(UpdateLeadRequest $request, Lead $lead): RedirectResponse
    {
        $data = $request->validated();
        if ($data['status'] !== LeadStatus::New->value && ! $lead->contacted_at) {
            $data['contacted_at'] = now();
        }
        $lead->update($data);

        return back()->with('success', __('admin/leads.updated'));
    }

    public function export(Request $request): StreamedResponse
    {
        $fileName = 'leads-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($request) {
            $output = fopen('php://output', 'wb');
            fputcsv($output, ['ID', 'Fecha', 'Origen', 'Estado', 'Nombre', 'Telefono', 'Email', 'Mensaje']);
            $this->filtered($request)->latest()->chunk(500, function ($leads) use ($output) {
                foreach ($leads as $lead) {
                    fputcsv($output, [$lead->id, $lead->created_at->toIso8601String(), $lead->source->value, $lead->status->value, $lead->name, $lead->phone, $lead->email, $lead->message]);
                }
            });
            fclose($output);
        }, $fileName, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function filtered(Request $request): Builder
    {
        return Lead::query()
            ->when($request->filled('status'), fn ($query) => $query->where('status', (string) $request->string('status')))
            ->when($request->filled('source'), fn ($query) => $query->where('source', (string) $request->string('source')))
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = '%'.str_replace(['%', '_'], ['\\%', '\\_'], (string) $request->string('q')).'%';
                $query->where(fn ($nested) => $nested->where('name', 'like', $term)->orWhere('email', 'like', $term)->orWhere('phone', 'like', $term));
            });
    }
}
