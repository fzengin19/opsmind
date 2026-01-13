<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use App\Data\AppointmentData;
use App\Actions\Appointments\CreateAppointmentAction;
use App\Actions\Appointments\DeleteAppointmentAction;
use App\Actions\Appointments\UpdateAppointmentAction;
use App\Models\Appointment;
use App\Models\Calendar;
use App\Enums\AppointmentType;
use App\Enums\CalendarType;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

new class extends Component {
    use AuthorizesRequests;

    public bool $showModal = false;
    public ?Appointment $appointment = null;

    // Form fields
    public ?int $calendar_id = null;
    public string $title = '';
    public string $type = 'meeting';
    public string $start_at = '';
    public string $end_at = '';
    public bool $all_day = false;
    public ?string $location = null;
    public ?string $description = null;

    public array $attendee_user_ids = [];
    public string $searchQuery = '';

    protected function rules(): array
    {
        return [
            'calendar_id' => 'required|exists:calendars,id',
            'title' => 'required|string|max:100',
            'type' => 'required|in:meeting,call,focus,break',
            'start_at' => 'required|date',
            'end_at' => 'required|date|after_or_equal:start_at',
            'all_day' => 'boolean',
            'location' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'attendee_user_ids' => 'array',
        ];
    }

    #[On('open-appointment-form')]
    public function open(?int $appointmentId = null, ?string $prefillDate = null, ?string $start_time = null): void
    {
        $this->resetForm();
        $this->showModal = true;

        if ($appointmentId) {
            $this->appointment = Appointment::with('attendees')->findOrFail($appointmentId);
            $this->fill([
                'calendar_id' => $this->appointment->calendar_id,
                'all_day' => (bool)$this->appointment->all_day,
                'title' => $this->appointment->title,
                'type' => $this->appointment->type->value,
                'start_at' => $this->appointment->start_at->format($this->appointment->all_day ? 'Y-m-d' : 'Y-m-d\TH:i'),
                'end_at' => $this->appointment->end_at->format($this->appointment->all_day ? 'Y-m-d' : 'Y-m-d\TH:i'),
                'location' => $this->appointment->location,
                'description' => $this->appointment->description,
            ]);
            $this->attendee_user_ids = $this->appointment->attendees
                ->whereNotNull('user_id')
                ->pluck('user_id')
                ->toArray();
        } else {
            $company = auth()->user()->currentCompany();
            $this->calendar_id = $company->defaultCalendar()?->id;

            // Oluşturan kişiyi default olarak seç
            $this->attendee_user_ids = [auth()->id()];

            if ($prefillDate) {
                // Use provided start_time or default to 09:00
                $time = $start_time ?? '09:00';
                $this->start_at = $prefillDate . 'T' . $time;

                // Set end time to 1 hour later
                $startCarbon = Carbon::parse($this->start_at);
                $this->end_at = $startCarbon->addHour()->format('Y-m-d\TH:i');
            } else {
                $now = now()->addHour()->startOfHour();
                $this->start_at = $now->format('Y-m-d\TH:i');
                $this->end_at = $now->addHour()->format('Y-m-d\TH:i');
            }
        }
    }

    public function save(
        CreateAppointmentAction $createAction,
        UpdateAppointmentAction $updateAction
    ): void {
        $this->validate();

        // Validate attendee count based on Calendar Type
        $calendar = Calendar::find($this->calendar_id);
        if ($calendar) {
            $isPersonal = $calendar->type === CalendarType::Personal || $calendar->is_default;
            if ($isPersonal && empty($this->attendee_user_ids)) {
                $this->addError('attendee_user_ids', __('calendar.error_attendee_required'));
                return;
            }
        }

        $user = auth()->user();
        $company = $user->currentCompany();

        $data = new AppointmentData(
            id: $this->appointment?->id,
            company_id: $company->id,
            calendar_id: $this->calendar_id,
            title: $this->title,
            type: AppointmentType::from($this->type),
            start_at: $this->all_day ? Carbon::parse($this->start_at)->startOfDay() : Carbon::parse($this->start_at),
            end_at: $this->all_day ? Carbon::parse($this->end_at)->endOfDay() : Carbon::parse($this->end_at),
            all_day: $this->all_day,
            location: $this->location,
            description: $this->description,
            attendee_user_ids: $this->attendee_user_ids,
        );

        if ($this->appointment) {
            $this->authorize('update', $this->appointment);
            $updateAction->execute($this->appointment, $data);
        } else {
            $this->authorize('create', Appointment::class);
            $createAction->execute($data, $user);
        }

        $this->showModal = false;
        $this->dispatch('calendar-refresh');
    }

    public function delete(DeleteAppointmentAction $action): void
    {
        $this->authorize('delete', $this->appointment);

        $action->execute($this->appointment);

        $this->showModal = false;
        $this->dispatch('calendar-refresh');
    }

    public function resetForm(): void
    {
        $this->reset([
            'appointment',
            'calendar_id',
            'title',
            'type',
            'start_at',
            'end_at',
            'all_day',
            'location',
            'description',
            'attendee_user_ids'
        ]);
        $this->type = 'meeting';
        $this->searchQuery = '';
    }

    #[Computed]
    public function searchUsers()
    {
        if (strlen($this->searchQuery) < 2) {
            return [];
        }

        return auth()->user()->currentCompany()->users()
            ->where(function ($q) {
                $q->where('name', 'like', "%{$this->searchQuery}%")
                    ->orWhere('email', 'like', "%{$this->searchQuery}%");
            })
            ->whereNotIn('users.id', $this->attendee_user_ids)
            ->take(5)
            ->get();
    }

    #[Computed]
    public function selectedUsers()
    {
        if (empty($this->attendee_user_ids)) {
            return collect();
        }

        return User::whereIn('id', $this->attendee_user_ids)->get();
    }

    public function addAttendee(int $userId): void
    {
        if (!in_array($userId, $this->attendee_user_ids)) {
            $this->attendee_user_ids[] = $userId;
        }
        $this->searchQuery = '';
    }

    public function removeAttendee(int $userId): void
    {
        $this->attendee_user_ids = array_values(array_diff($this->attendee_user_ids, [$userId]));
    }

    public $calendars = []; // Passed from parent

    public function with(): array
    {
        return [
            'availableCalendars' => $this->calendars, // Alias for backward compatibility if needed, or just use $calendars in view
        ];
    }
}; ?>

<div x-data="{ 
    localOpen: false, 
    init() {
        // Wire model ile senkronize ol
        this.$watch('$wire.showModal', value => {
            this.localOpen = value;
            if (value) {
                // Açılırken hemen bildir
                this.$dispatch('modal-toggled', { isOpen: true });
            } else {
                // Kapanırken gecikmeli bildir (ESC çakışmasını önlemek için)
                setTimeout(() => this.$dispatch('modal-toggled', { isOpen: false }), 200);
            }
        });
    }
}" @keydown.window.escape="if($wire.showModal) $wire.set('showModal', false)">
    <flux:modal wire:model="showModal" class="max-w-2xl">
        <div class="p-6 space-y-6">
            <flux:heading size="lg">
                {{ $appointment ? __('calendar.edit_event') : __('calendar.new_event') }}
            </flux:heading>

            <form wire:submit="save" class="space-y-4">
                {{-- Takvim Seçimi --}}
                <flux:select wire:model="calendar_id" label="{{ __('calendar.calendar') }}" required>
                    <option value="">{{ __('calendar.select_calendar') }}</option>
                    @foreach($calendars as $calendar)
                        <option value="{{ $calendar->id }}">{{ $calendar->name }}</option>
                    @endforeach
                </flux:select>

                {{-- Başlık --}}
                <flux:input wire:model.blur="title" label="{{ __('calendar.event_title') }}"
                    placeholder="{{ __('calendar.event_title_placeholder') }}" required />

                {{-- Tür --}}
                <flux:select wire:model="type" label="{{ __('calendar.event_type') }}" required>
                    @foreach(\App\Enums\AppointmentType::cases() as $t)
                        <option value="{{ $t->value }}">{{ $t->label() }}</option>
                    @endforeach
                </flux:select>

                {{-- Tarih/Saat --}}
                <div class="grid grid-cols-2 gap-4">
                    @if($all_day)
                        <flux:input wire:key="date-start" type="date" wire:model.blur="start_at" label="{{ __('calendar.start') }}" required />
                        <flux:input wire:key="date-end" type="date" wire:model.blur="end_at" label="{{ __('calendar.end') }}" required />
                    @else
                        <flux:input wire:key="datetime-start" type="datetime-local" wire:model.blur="start_at" label="{{ __('calendar.start') }}"
                            required />
                        <flux:input wire:key="datetime-end" type="datetime-local" wire:model.blur="end_at" label="{{ __('calendar.end') }}"
                            required />
                    @endif
                </div>

                {{-- Tüm gün --}}
                <flux:checkbox wire:model.live="all_day" label="{{ __('calendar.all_day') }}" />

                {{-- Konum --}}
                <flux:input wire:model.blur="location" label="{{ __('calendar.location') }}"
                    placeholder="{{ __('calendar.location_placeholder') }}" />

                {{-- Katılımcılar --}}
                <div>
                    <label class="block text-sm font-medium mb-2">{{ __('calendar.attendees') }}</label>

                    {{-- Selected Users (Chips) --}}
                    <div class="flex flex-wrap gap-2 mb-2">
                        @foreach($this->selectedUsers as $user)
                            <flux:badge size="sm" class="gap-1 pr-1">
                                {{ $user->name }}
                                <div wire:click="removeAttendee({{ $user->id }})" class="cursor-pointer hover:text-red-500">
                                    <flux:icon name="x-mark" size="xs" />
                                </div>
                            </flux:badge>
                        @endforeach
                    </div>

                    {{-- Search Input --}}
                    <div class="relative">
                        <flux:input wire:model.live.debounce.300ms="searchQuery" icon="magnifying-glass"
                            placeholder="{{ __('calendar.search_attendees') }}" autocomplete="off" />

                        {{-- Search Results Dropdown --}}
                        @if(!empty($this->searchQuery) && count($this->searchUsers) > 0)
                            <div
                                class="absolute z-50 w-full mt-1 bg-white border rounded-lg shadow-lg dark:bg-zinc-800 border-zinc-200 dark:border-zinc-700 max-h-48 overflow-y-auto">
                                @foreach($this->searchUsers as $user)
                                    <div wire:click="addAttendee({{ $user->id }})"
                                        class="flex items-center gap-3 px-4 py-2 cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-700/50 transition-colors">
                                        <flux:avatar size="xs" :name="$user->name" />
                                        <div class="flex-1 min-w-0">
                                            <div class="text-sm font-medium truncate">{{ $user->name }}</div>
                                            <div class="text-xs text-zinc-500 truncate">{{ $user->email }}</div>
                                        </div>
                                        <flux:icon name="plus" size="xs" class="text-zinc-400" />
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                    <flux:error name="attendee_user_ids" />
                </div>

                {{-- Açıklama --}}
                <flux:textarea wire:model.blur="description" label="{{ __('calendar.description') }}" rows="3" />

                {{-- Butonlar --}}
                <div class="flex justify-end gap-2 pt-4">
                    <flux:button variant="ghost" type="button" wire:click="$set('showModal', false)">
                        {{ __('common.cancel') }}
                    </flux:button>

                    @if($appointment)
                        <flux:button variant="danger" type="button" wire:click="delete"
                            wire:confirm="{{ __('calendar.delete_confirmation', ['title' => $appointment->title]) }}">
                            {{ __('common.delete') }}
                        </flux:button>
                    @endif
                    <flux:button type="submit" variant="primary">
                        {{ $appointment ? __('common.update') : __('common.create') }}
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>
</div>