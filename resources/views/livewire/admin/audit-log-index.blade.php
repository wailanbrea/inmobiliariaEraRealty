@php
    $campo = 'h-11 w-full rounded-lg border border-outline-variant bg-surface-container-lowest
              px-sm text-body-md text-on-surface outline-none transition-shadow
              focus:border-secondary focus:ring-2 focus:ring-secondary';

    $tonos = [
        'danger'  => 'bg-error-container text-on-error-container',
        'warning' => 'bg-tertiary-fixed text-on-tertiary-fixed',
        'neutral' => 'bg-surface-container text-on-surface-variant',
    ];
@endphp

<div class="space-y-md">

    {{-- ============================== FILTROS ============================== --}}
    <div class="rounded-xl border border-outline-variant/40 bg-surface-container-lowest p-sm ambient-shadow">
        <div class="grid grid-cols-1 gap-sm sm:grid-cols-2 lg:grid-cols-4">

            <label class="block">
                <span class="mb-1 block text-caption text-on-surface-variant">
                    {{ __('admin/audit.filters.action') }}
                </span>
                <select wire:model.live="action" class="{{ $campo }}">
                    <option value="">{{ __('admin/audit.filters.all_actions') }}</option>
                    @foreach ($acciones as $accion)
                        <option value="{{ $accion->value }}">{{ $accion->label() }}</option>
                    @endforeach
                </select>
            </label>

            <label class="block">
                <span class="mb-1 block text-caption text-on-surface-variant">
                    {{ __('admin/audit.filters.user') }}
                </span>
                <select wire:model.live="user" class="{{ $campo }}">
                    <option value="">{{ __('admin/audit.filters.all_users') }}</option>
                    @foreach ($this->authors as $autor)
                        <option value="{{ $autor->id }}">{{ $autor->name }}</option>
                    @endforeach
                </select>
            </label>

            <label class="block">
                <span class="mb-1 block text-caption text-on-surface-variant">
                    {{ __('admin/audit.filters.from') }}
                </span>
                <input type="date" wire:model.live="from" class="{{ $campo }}">
            </label>

            <label class="block">
                <span class="mb-1 block text-caption text-on-surface-variant">
                    {{ __('admin/audit.filters.to') }}
                </span>
                <input type="date" wire:model.live="to" class="{{ $campo }}">
            </label>
        </div>

        @if ($action || $user || $from || $to)
            <button type="button" wire:click="clearFilters"
                    class="mt-sm inline-flex items-center gap-xs rounded-lg border border-outline-variant
                           px-sm py-xs text-label-md text-on-surface transition-colors
                           hover:bg-surface-container-low">
                <span class="material-symbols-outlined text-[18px]">filter_alt_off</span>
                {{ __('admin/audit.filters.clear') }}
            </button>
        @endif
    </div>

    {{-- ============================== LISTADO ============================== --}}
    @if ($logs->isEmpty())
        <div class="rounded-xl border border-outline-variant/40 bg-surface-container-lowest p-xl text-center">
            <span class="material-symbols-outlined text-[48px] text-outline-variant">history</span>
            <p class="mt-xs text-title-lg text-on-surface">{{ __('admin/audit.empty.title') }}</p>
            <p class="mt-1 text-body-md text-on-surface-variant">{{ __('admin/audit.empty.body') }}</p>
        </div>
    @else
        {{-- La tabla scrollea DENTRO de su contenedor, no la pagina.
             Ver docs/14_RESPONSIVE.md seccion 2. --}}
        <div class="overflow-x-auto rounded-xl border border-outline-variant/40
                    bg-surface-container-lowest ambient-shadow">
            <table class="w-full min-w-[720px] text-left">
                <thead class="border-b border-outline-variant/40 text-caption uppercase
                              tracking-wider text-on-surface-variant">
                    <tr>
                        <th scope="col" class="px-sm py-xs">{{ __('admin/audit.table.when') }}</th>
                        <th scope="col" class="px-sm py-xs">{{ __('admin/audit.table.who') }}</th>
                        <th scope="col" class="px-sm py-xs">{{ __('admin/audit.table.what') }}</th>
                        <th scope="col" class="px-sm py-xs">{{ __('admin/audit.table.target') }}</th>
                        <th scope="col" class="px-sm py-xs"><span class="sr-only">{{ __('admin/audit.table.detail') }}</span></th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-outline-variant/30">
                    @foreach ($logs as $log)
                        <tr class="transition-colors hover:bg-surface-container-low">
                            <td class="whitespace-nowrap px-sm py-xs text-body-md text-on-surface-variant">
                                <time datetime="{{ $log->created_at->toAtomString() }}">
                                    {{ $log->created_at->format('d/m/Y H:i') }}
                                </time>
                            </td>

                            <td class="px-sm py-xs text-body-md text-on-surface">
                                {{ $log->authorName() }}
                            </td>

                            <td class="px-sm py-xs">
                                <span class="inline-flex items-center gap-xs rounded-full px-xs py-1
                                             text-caption font-semibold {{ $tonos[$log->action->tone()] }}">
                                    <span class="material-symbols-outlined text-[16px]">{{ $log->action->icon() }}</span>
                                    {{ $log->action->label() }}
                                </span>
                            </td>

                            <td class="max-w-xs truncate px-sm py-xs text-body-md text-on-surface-variant">
                                {{ $log->entity_label ?: '—' }}
                            </td>

                            <td class="px-sm py-xs text-right">
                                <button type="button" wire:click="view({{ $log->id }})"
                                        aria-label="{{ __('admin/audit.table.detail') }}"
                                        class="rounded-lg p-1 text-on-surface-variant transition-colors
                                               hover:bg-surface-container hover:text-secondary">
                                    <span class="material-symbols-outlined text-[20px]">visibility</span>
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div>{{ $logs->links() }}</div>
    @endif

    {{-- ============================== DETALLE ============================== --}}
    @if ($this->detail)
        @php $log = $this->detail; $diff = $log->diff(); @endphp

        <div class="fixed inset-0 z-50 flex items-end justify-center bg-primary/50 p-0 sm:items-center sm:p-sm"
             role="dialog" aria-modal="true" aria-labelledby="detalle-auditoria"
             wire:keydown.escape="closeDetail">

            <div class="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-t-xl bg-surface-container-lowest
                        p-sm shadow-ambient-lg sm:rounded-xl sm:p-md">

                <div class="mb-sm flex items-start justify-between gap-sm">
                    <div>
                        <h2 id="detalle-auditoria" class="text-title-lg text-on-surface">
                            {{ $log->action->label() }}
                        </h2>
                        <p class="mt-1 text-body-md text-on-surface-variant">
                            {{ $log->authorName() }} · {{ $log->created_at->format('d/m/Y H:i:s') }}
                        </p>
                        @if ($log->entity_label)
                            <p class="mt-1 text-body-md text-on-surface">{{ $log->entity_label }}</p>
                        @endif
                    </div>

                    <button type="button" wire:click="closeDetail"
                            aria-label="{{ __('admin/audit.detail.close') }}"
                            class="rounded-lg p-1 text-on-surface-variant transition-colors hover:text-secondary">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>

                @if ($diff === [])
                    <p class="rounded-lg bg-surface-container-low p-sm text-body-md text-on-surface-variant">
                        {{ __('admin/audit.detail.no_changes') }}
                    </p>
                @else
                    <div class="overflow-x-auto rounded-lg border border-outline-variant/40">
                        <table class="w-full min-w-[520px] text-left text-body-md">
                            <thead class="border-b border-outline-variant/40 text-caption uppercase
                                          tracking-wider text-on-surface-variant">
                                <tr>
                                    <th scope="col" class="px-sm py-xs">{{ __('admin/audit.detail.field') }}</th>
                                    <th scope="col" class="px-sm py-xs">{{ __('admin/audit.detail.before') }}</th>
                                    <th scope="col" class="px-sm py-xs">{{ __('admin/audit.detail.after') }}</th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-outline-variant/30">
                                @foreach ($diff as $campoNombre => $valores)
                                    <tr>
                                        <th scope="row" class="px-sm py-xs font-medium text-on-surface">
                                            {{ $campoNombre }}
                                        </th>

                                        @foreach (['old', 'new'] as $lado)
                                            @php $valor = $valores[$lado]; @endphp
                                            <td @class([
                                                'px-sm py-xs align-top',
                                                'text-error' => $lado === 'old',
                                                'text-status-available' => $lado === 'new',
                                            ])>
                                                @if ($valor === \App\Modules\Audit\Services\AuditService::MASK)
                                                    <span class="italic text-on-surface-variant"
                                                          title="{{ __('admin/audit.detail.redacted') }}">
                                                        {{ $valor }}
                                                    </span>
                                                @elseif ($valor === null || $valor === '')
                                                    <span class="italic text-outline">{{ __('admin/audit.detail.empty_value') }}</span>
                                                @else
                                                    <span class="break-words">
                                                        {{ \Illuminate\Support\Str::limit(is_scalar($valor) ? (string) $valor : json_encode($valor, JSON_UNESCAPED_UNICODE), 120) }}
                                                    </span>
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                <dl class="mt-sm grid grid-cols-1 gap-xs text-caption text-on-surface-variant sm:grid-cols-2">
                    <div>
                        <dt class="font-semibold">{{ __('admin/audit.detail.ip') }}</dt>
                        <dd>{{ $log->ip_address ?: '—' }}</dd>
                    </div>
                    <div class="min-w-0">
                        <dt class="font-semibold">{{ __('admin/audit.detail.agent') }}</dt>
                        <dd class="truncate" title="{{ $log->user_agent }}">{{ $log->user_agent ?: '—' }}</dd>
                    </div>
                </dl>
            </div>
        </div>
    @endif
</div>
