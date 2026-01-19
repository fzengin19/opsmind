<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use App\Actions\Calendars\CreateCalendarAction;
use App\Actions\Calendars\UpdateCalendarAction;
use App\Actions\Calendars\DeleteCalendarAction;
use App\Data\CalendarData;
use App\Enums\CalendarType;
use App\Enums\CalendarVisibility;
use App\Models\Calendar;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Collection;

new class extends Component {
    use AuthorizesRequests;

    public bool $showModal = false;
    public bool $showFormModal = false;
    public ?Calendar $editingCalendar = null;

    // Form fields
    public string $name = '';
    public string $color = '#3b82f6';
    public string $type = 'team';
    public string $visibility = 'company_wide';

    // Preset colors
    public array $presetColors = [
        '#ef4444', // Red
        '#f97316', // Orange
        '#eab308', // Yellow
        '#22c55e', // Green
        '#14b8a6', // Teal
        '#3b82f6', // Blue
        '#8b5cf6', // Violet
        '#ec4899', // Pink
        '#6b7280', // Gray
    ];

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:100',
            'color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'type' => 'required|in:team,resource',
            'visibility' => 'required|in:company_wide,members_only,private',
        ];
    }

    #[On('open-calendar-manager')]
    public function open(): void
    {
        $this->resetForm();
        $this->showModal = true;
        $this->showFormModal = false;
    }

    public function startCreate(): void
    {
        $this->authorize('create', Calendar::class);
        $this->resetForm();
        $this->showFormModal = true;
    }

    public function startEdit(int $calendarId): void
    {
        $this->editingCalendar = Calendar::findOrFail($calendarId);
        $this->authorize('update', $this->editingCalendar);

        $this->fill([
            'name' => $this->editingCalendar->name,
            'color' => $this->editingCalendar->color,
            'type' => $this->editingCalendar->type->value,
            'visibility' => $this->editingCalendar->visibility->value,
        ]);

        $this->showFormModal = true;
    }

    public function save(
        CreateCalendarAction $createAction,
        UpdateCalendarAction $updateAction
    ): void {
        $this->validate();

        $data = new CalendarData(
            id: $this->editingCalendar?->id,
            name: $this->name,
            color: $this->color,
            type: CalendarType::from($this->type),
            visibility: CalendarVisibility::from($this->visibility),
        );

        if ($this->editingCalendar) {
            $updateAction->execute($this->editingCalendar, $data);
            session()->flash('calendar-message', __('calendar.calendar_updated'));
        } else {
            $createAction->execute($data, auth()->user());
            session()->flash('calendar-message', __('calendar.calendar_created'));
        }

        $this->resetForm();
        $this->showFormModal = false;
        $this->dispatch('calendar-refresh');
    }

    public function delete(int $calendarId, DeleteCalendarAction $action): void
    {
        $calendar = Calendar::findOrFail($calendarId);
        $this->authorize('delete', $calendar);

        try {
            $action->execute($calendar);
            session()->flash('calendar-message', __('calendar.calendar_deleted'));
            $this->dispatch('calendar-refresh');
        } catch (\DomainException $e) {
            session()->flash('calendar-error', $e->getMessage());
        }
    }

    public function resetForm(): void
    {
        $this->reset(['editingCalendar', 'name', 'type', 'visibility']);
        $this->color = '#3b82f6';
        $this->type = 'team';
        $this->visibility = 'company_wide';
        $this->resetValidation();
    }

    public function closeFormModal(): void
    {
        $this->resetForm();
        $this->showFormModal = false;
    }

    #[Computed]
    public function calendars(): Collection
    {
        $user = auth()->user();
        if (!$user || !$user->currentCompany()) {
            return collect();
        }

        // Only show calendars the user has access to
        // Personal calendars only visible to their owner
        return Calendar::accessibleBy($user)
            ->where('company_id', $user->currentCompany()->id)
            ->orderByDesc('is_default')
            ->orderBy('type')
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function canCreate(): bool
    {
        return auth()->user()?->can('create', Calendar::class) ?? false;
    }
}; ?>

<div>
    {{-- Main List Modal --}}
    <flux:modal wire:model="showModal" class="max-w-2xl"
        x-on:keydown.escape.window="if ($wire.showModal && !$wire.showFormModal) $wire.set('showModal', false)">
        <div class="p-6">
            {{-- Header --}}
            <div class="flex items-center justify-between mb-6">
                <flux:heading size="lg">{{ __('calendar.manage_calendars') }}</flux:heading>

                @if($this->canCreate)
                    <flux:button variant="primary" size="sm" icon="plus" wire:click="startCreate">
                        {{ __('calendar.new_calendar') }}
                    </flux:button>
                @endif
            </div>

            {{-- Flash Messages --}}
            @if(session()->has('calendar-message'))
                <flux:callout variant="success" class="mb-4">
                    {{ session('calendar-message') }}
                </flux:callout>
            @endif

            @if(session()->has('calendar-error'))
                <flux:callout variant="danger" class="mb-4">
                    {{ session('calendar-error') }}
                </flux:callout>
            @endif

            {{-- Calendar List --}}
            <div class="space-y-2">
                @forelse($this->calendars as $calendar)
                    <div wire:key="calendar-{{ $calendar->id }}"
                        class="flex items-center justify-between p-3 rounded-lg border border-zinc-200 dark:border-zinc-700 hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition">
                        <div class="flex items-center gap-3">
                            {{-- Color Dot --}}
                            <span class="size-4 rounded-full flex-shrink-0"
                                style="background-color: {{ $calendar->color }}"></span>

                            {{-- Name & Badges --}}
                            <div>
                                <div class="font-medium text-zinc-900 dark:text-zinc-100">
                                    {{ $calendar->name }}
                                </div>
                                <div class="flex items-center gap-2 mt-0.5">
                                    <flux:badge size="sm" variant="outline">
                                        {{ $calendar->type->label() }}
                                    </flux:badge>
                                    @if($calendar->is_default)
                                        <flux:badge size="sm" color="primary">
                                            {{ __('calendar.default') }}
                                        </flux:badge>
                                    @endif
                                    @if($calendar->visibility !== App\Enums\CalendarVisibility::CompanyWide)
                                        <flux:badge size="sm" variant="outline" color="zinc">
                                            {{ $calendar->visibility->label() }}
                                        </flux:badge>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- Actions --}}
                        <div class="flex items-center gap-1">
                            @can('update', $calendar)
                                <flux:button variant="ghost" size="sm" icon="pencil"
                                    wire:click="startEdit({{ $calendar->id }})" />
                            @endcan

                            @can('delete', $calendar)
                                <flux:button variant="ghost" size="sm" icon="trash" class="text-red-500 hover:text-red-600"
                                    wire:click="delete({{ $calendar->id }})"
                                    wire:confirm="{{ __('calendar.calendar_delete_confirm', ['name' => $calendar->name]) }}" />
                            @endcan
                        </div>
                    </div>
                @empty
                    <div class="text-center py-8 text-zinc-500">
                        <flux:icon name="calendar" class="size-12 mx-auto mb-2 opacity-50" />
                        <p>{{ __('calendar.no_calendars') }}</p>
                    </div>
                @endforelse
            </div>

            {{-- Close Button --}}
            <div class="flex justify-end mt-6">
                <flux:button variant="ghost" wire:click="$set('showModal', false)">
                    {{ __('common.close') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Nested Form Modal (Create/Edit) --}}
    <flux:modal wire:model="showFormModal" class="max-w-md"
        x-on:keydown.escape.window="if ($wire.showFormModal) $wire.closeFormModal()">
        <div class="p-6">
            <flux:heading size="lg" class="mb-6">
                {{ $editingCalendar ? __('calendar.edit_calendar') : __('calendar.new_calendar') }}
            </flux:heading>

            <form wire:submit="save" class="space-y-4">
                {{-- Name --}}
                <flux:input wire:model.blur="name" label="{{ __('calendar.calendar_name') }}"
                    placeholder="{{ __('calendar.calendar_name_placeholder') }}" required />

                {{-- Color Picker --}}
                <div>
                    <label class="block text-sm font-medium mb-2">{{ __('calendar.calendar_color') }}</label>

                    {{-- Preset Colors --}}
                    <div class="flex flex-wrap gap-2 mb-3">
                        @foreach($presetColors as $preset)
                            <button type="button" wire:click="$set('color', '{{ $preset }}')"
                                class="size-8 rounded-full border-2 transition-transform hover:scale-110 {{ $color === $preset ? 'border-zinc-900 dark:border-white ring-2 ring-offset-2 ring-primary-500' : 'border-transparent' }}"
                                style="background-color: {{ $preset }}"></button>
                        @endforeach
                    </div>

                    {{-- Custom Color Input --}}
                    <div class="flex items-center gap-3">
                        <input type="color" wire:model.live="color"
                            class="size-10 rounded cursor-pointer border border-zinc-300 dark:border-zinc-600" />
                        <flux:input type="text" wire:model.blur="color" class="w-28 font-mono" placeholder="#3b82f6" />
                    </div>
                    <flux:error name="color" />
                </div>

                {{-- Type (only for create) --}}
                @if(!$editingCalendar)
                    <flux:select wire:model="type" label="{{ __('calendar.calendar_type') }}" required>
                        <option value="team">{{ __('enums.calendar_type.team') }}</option>
                        <option value="resource">{{ __('enums.calendar_type.resource') }}</option>
                    </flux:select>
                @endif

                {{-- Visibility (only for Team/Resource) --}}
                @if(!$editingCalendar || in_array($editingCalendar?->type->value, ['team', 'resource']))
                    <flux:select wire:model="visibility" label="{{ __('calendar.calendar_visibility') }}" required>
                        @foreach(App\Enums\CalendarVisibility::cases() as $v)
                            <option value="{{ $v->value }}">{{ $v->label() }}</option>
                        @endforeach
                    </flux:select>
                @endif

                {{-- Actions --}}
                <div class="flex justify-end gap-2 pt-4">
                    <flux:button variant="ghost" type="button" wire:click="closeFormModal">
                        {{ __('common.cancel') }}
                    </flux:button>
                    <flux:button variant="primary" type="submit">
                        {{ $editingCalendar ? __('common.update') : __('common.create') }}
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>
</div>