@php
    $campo = 'h-11 w-full rounded-lg border border-outline-variant bg-surface-container-lowest
              px-sm text-body-md text-on-surface outline-none transition-shadow
              focus:border-secondary focus:ring-2 focus:ring-secondary';

    $tonoRol = [
        'super_admin' => 'bg-primary-container text-on-primary',
        'admin'       => 'bg-secondary text-on-secondary',
        'editor'      => 'bg-surface-container text-on-surface-variant',
        'agent'       => 'bg-surface-container text-on-surface-variant',
    ];
@endphp

<div class="space-y-md">

    {{-- ========================== AVISOS ========================== --}}
    @if ($successMessage)
        <div class="rounded-lg bg-status-available/15 p-sm text-body-md text-on-surface" role="status">
            {{ $successMessage }}
        </div>
    @endif

    @if ($errorMessage)
        <div class="rounded-lg bg-error-container p-sm text-body-md text-on-error-container" role="alert">
            {{ $errorMessage }}
        </div>
    @endif

    {{-- ================= CONTRASEÑA GENERADA ================= --}}
    {{--
        Se muestra UNA sola vez y no se guarda en claro en ninguna parte.
        Es la pieza que permite dar de alta usuarios sin correo configurado.
    --}}
    @if ($generatedPassword)
        <div class="rounded-xl border-2 border-secondary bg-surface-container-lowest p-sm ambient-shadow"
             role="alert" x-data="{ copiado: false }">

            <div class="flex items-start gap-sm">
                <span class="material-symbols-outlined text-[24px] text-secondary">key</span>

                <div class="min-w-0 flex-1">
                    <h3 class="text-title-lg text-on-surface">
                        {{ __('admin/users.generated.title', ['name' => $generatedFor]) }}
                    </h3>
                    <p class="mt-1 text-body-md text-on-surface-variant">
                        {{ __('admin/users.generated.body') }}
                    </p>

                    <div class="mt-sm flex flex-wrap items-center gap-xs">
                        <code x-ref="clave"
                              class="select-all rounded-lg bg-surface-container px-sm py-xs
                                     font-mono text-title-lg tracking-widest text-on-surface">{{ $generatedPassword }}</code>

                        <button type="button"
                                x-on:click="navigator.clipboard.writeText($refs.clave.textContent).then(() => copiado = true)"
                                class="flex items-center gap-xs rounded-lg border border-outline-variant px-sm py-xs
                                       text-label-md text-on-surface transition-colors hover:bg-surface-container-low">
                            <span class="material-symbols-outlined text-[18px]"
                                  x-text="copiado ? 'check' : 'content_copy'">content_copy</span>
                            <span x-text="copiado ? '{{ __('admin/users.actions.understood') }}' : '{{ __('admin/users.actions.copy') }}'">
                                {{ __('admin/users.actions.copy') }}
                            </span>
                        </button>
                    </div>

                    <p class="mt-xs text-caption text-on-surface-variant">
                        {{ __('admin/users.generated.must_change') }}
                    </p>
                </div>

                <button type="button" wire:click="dismissPassword"
                        aria-label="{{ __('common.actions.close') }}"
                        class="rounded-lg p-1 text-on-surface-variant transition-colors hover:text-secondary">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
        </div>
    @endif

    {{-- =========================== ALTA =========================== --}}
    <div class="flex flex-wrap items-center justify-between gap-sm">
        <p class="text-body-md text-on-surface-variant">{{ __('admin/users.no_mail_notice') }}</p>

        @unless ($showForm)
            <button type="button" wire:click="create"
                    class="flex items-center gap-xs rounded-lg bg-primary-container px-md py-xs
                           text-label-md font-semibold text-on-primary transition-all hover-lift">
                <span class="material-symbols-outlined text-[18px]">person_add</span>
                {{ __('admin/users.actions.new') }}
            </button>
        @endunless
    </div>

    {{-- ========================= FORMULARIO ========================= --}}
    @if ($showForm)
        <form wire:submit="save"
              class="rounded-xl border border-outline-variant/40 bg-surface-container-lowest p-sm ambient-shadow">

            <div class="grid grid-cols-1 gap-sm md:grid-cols-2">
                <label class="block">
                    <span class="mb-1 block text-caption text-on-surface-variant">{{ __('admin/users.fields.name') }}</span>
                    <input type="text" wire:model="name" class="{{ $campo }}" required>
                    @error('name') <span class="mt-1 block text-caption text-error">{{ $message }}</span> @enderror
                </label>

                <label class="block">
                    <span class="mb-1 block text-caption text-on-surface-variant">{{ __('admin/users.fields.email') }}</span>
                    <input type="email" wire:model="email" class="{{ $campo }}" required>
                    @error('email') <span class="mt-1 block text-caption text-error">{{ $message }}</span> @enderror
                </label>

                <label class="block">
                    <span class="mb-1 block text-caption text-on-surface-variant">{{ __('admin/users.fields.phone') }}</span>
                    <input type="tel" wire:model="phone" class="{{ $campo }}">
                </label>

                <label class="block">
                    <span class="mb-1 block text-caption text-on-surface-variant">{{ __('admin/users.fields.role') }}</span>
                    <select wire:model.live="role" class="{{ $campo }}">
                        @foreach ($roles as $r)
                            <option value="{{ $r }}">{{ __('admin/users.roles.'.$r) }}</option>
                        @endforeach
                    </select>
                    <span class="mt-1 block text-caption text-on-surface-variant">
                        {{ __('admin/users.roles_help.'.$role) }}
                    </span>
                    @error('role') <span class="mt-1 block text-caption text-error">{{ $message }}</span> @enderror
                </label>
            </div>

            <div class="mt-sm flex items-center gap-xs">
                <button type="submit"
                        class="rounded-lg bg-primary-container px-md py-xs text-label-md font-semibold
                               text-on-primary transition-all hover-lift">
                    {{ __('admin/users.actions.save') }}
                </button>

                <button type="button" wire:click="cancel"
                        class="rounded-lg border border-outline-variant px-md py-xs text-label-md
                               text-on-surface transition-colors hover:bg-surface-container-low">
                    {{ __('admin/users.actions.cancel') }}
                </button>
            </div>
        </form>
    @endif

    {{-- ========================== LISTADO ========================== --}}
    <div class="table-scroll rounded-xl border border-outline-variant/40
                bg-surface-container-lowest ambient-shadow">
        <table class="w-full min-w-[760px] text-left">
            <thead class="border-b border-outline-variant/40 text-caption uppercase
                          tracking-wider text-on-surface-variant">
                <tr>
                    <th scope="col" class="px-sm py-xs">{{ __('admin/users.fields.name') }}</th>
                    <th scope="col" class="px-sm py-xs">{{ __('admin/users.fields.role') }}</th>
                    <th scope="col" class="px-sm py-xs">{{ __('admin/users.fields.status') }}</th>
                    <th scope="col" class="px-sm py-xs text-right">
                        <span class="sr-only">{{ __('admin/users.actions.edit') }}</span>
                    </th>
                </tr>
            </thead>

            <tbody class="divide-y divide-outline-variant/30">
                @foreach ($usuarios as $usuario)
                    @php $rol = $usuario->getRoleNames()->first() ?? 'editor'; @endphp

                    <tr @class([
                        'transition-colors hover:bg-surface-container-low',
                        'opacity-60' => ! $usuario->is_active,
                    ])>
                        <td class="px-sm py-xs">
                            <span class="block text-body-md text-on-surface">
                                {{ $usuario->name }}
                                @if ($usuario->is(auth()->user()))
                                    <span class="ml-1 rounded-full bg-surface-container px-xs py-0.5
                                                 text-caption text-on-surface-variant">tú</span>
                                @endif
                            </span>
                            <span class="block truncate text-caption text-on-surface-variant">{{ $usuario->email }}</span>
                        </td>

                        <td class="px-sm py-xs">
                            <span class="inline-flex rounded-full px-xs py-0.5 text-caption font-semibold
                                         {{ $tonoRol[$rol] ?? $tonoRol['editor'] }}">
                                {{ __('admin/users.roles.'.$rol) }}
                            </span>
                        </td>

                        <td class="px-sm py-xs text-body-md">
                            @if (! $usuario->is_active)
                                <span class="text-error">{{ __('admin/users.status.inactive') }}</span>
                            @elseif ($usuario->must_change_password)
                                <span class="text-status-reserved">{{ __('admin/users.status.pending_password') }}</span>
                            @else
                                <span class="text-status-available">{{ __('admin/users.status.active') }}</span>
                            @endif
                        </td>

                        <td class="whitespace-nowrap px-sm py-xs text-right">
                            <button type="button" wire:click="edit({{ $usuario->id }})"
                                    title="{{ __('admin/users.actions.edit') }}"
                                    aria-label="{{ __('admin/users.actions.edit') }}"
                                    class="rounded-lg p-1 text-on-surface-variant transition-colors
                                           hover:bg-surface-container hover:text-secondary">
                                <span class="material-symbols-outlined text-[20px]">edit</span>
                            </button>

                            <button type="button" wire:click="resetPassword({{ $usuario->id }})"
                                    title="{{ __('admin/users.actions.reset_password') }}"
                                    aria-label="{{ __('admin/users.actions.reset_password') }}"
                                    class="rounded-lg p-1 text-on-surface-variant transition-colors
                                           hover:bg-surface-container hover:text-secondary">
                                <span class="material-symbols-outlined text-[20px]">key</span>
                            </button>

                            <button type="button" wire:click="toggleActive({{ $usuario->id }})"
                                    title="{{ $usuario->is_active ? __('admin/users.actions.deactivate') : __('admin/users.actions.activate') }}"
                                    aria-label="{{ $usuario->is_active ? __('admin/users.actions.deactivate') : __('admin/users.actions.activate') }}"
                                    class="rounded-lg p-1 text-on-surface-variant transition-colors
                                           hover:bg-surface-container hover:text-secondary">
                                <span class="material-symbols-outlined text-[20px]">
                                    {{ $usuario->is_active ? 'toggle_on' : 'toggle_off' }}
                                </span>
                            </button>

                            <button type="button" wire:click="confirmDelete({{ $usuario->id }})"
                                    title="{{ __('admin/users.actions.delete') }}"
                                    aria-label="{{ __('admin/users.actions.delete') }}"
                                    class="rounded-lg p-1 text-on-surface-variant transition-colors
                                           hover:bg-error-container hover:text-error">
                                <span class="material-symbols-outlined text-[20px]">delete</span>
                            </button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- ====================== CONFIRMAR BORRADO ====================== --}}
    @if ($confirmingDelete)
        @php $victima = $usuarios->firstWhere('id', $confirmingDelete); @endphp

        <div class="fixed inset-0 z-50 flex items-end justify-center bg-primary/50 p-0 sm:items-center sm:p-sm"
             role="dialog" aria-modal="true" wire:keydown.escape="cancelDelete">

            <div class="w-full max-w-md rounded-t-xl bg-surface-container-lowest p-md
                        shadow-ambient-lg sm:rounded-xl">
                <h2 class="text-title-lg text-on-surface">
                    {{ __('admin/users.delete.title', ['name' => $victima?->name]) }}
                </h2>
                <p class="mt-xs text-body-md text-on-surface-variant">
                    {{ __('admin/users.delete.body') }}
                </p>

                <div class="mt-md flex flex-col-reverse gap-xs sm:flex-row sm:justify-end">
                    <button type="button" wire:click="cancelDelete"
                            class="rounded-lg border border-outline-variant px-md py-xs text-label-md
                                   text-on-surface transition-colors hover:bg-surface-container-low">
                        {{ __('admin/users.actions.cancel') }}
                    </button>

                    <button type="button" wire:click="delete"
                            class="rounded-lg bg-error px-md py-xs text-label-md font-semibold
                                   text-on-error transition-all hover-lift">
                        {{ __('admin/users.delete.confirm') }}
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
