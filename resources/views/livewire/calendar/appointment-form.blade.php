<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use App\Data\AppointmentData;
use App\Actions\Appointments\CreateAppointmentAction;
use App\Actions\Appointments\UpdateAppointmentAction;
use App\Models\Appointment;
use App\Models\Calendar;
use App\Enums\AppointmentType;
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
    public function open(?int $appointmentId = null, ?string $prefillDate = null): void
    {
        $this->resetForm();
        $this->showModal = true;

        if ($appointmentId) {
            $this->appointment = Appointment::with('attendees')->findOrFail($appointmentId);
            $this->fill([
                'calendar_id' => $this->appointment->calendar_id,
                'title' => $this->appointment->title,
                'type' => $this->appointment->type->value,
                'start_at' => $this->appointment->start_at->format('Y-m-d\TH:i'),
                'end_at' => $this->appointment->end_at->format('Y-m-d\TH:i'),
                'all_day' => $this->appointment->all_day,
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
                $this->start_at = $prefillDate . 'T09:00';
                $this->end_at = $prefillDate . 'T10:00';
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

        $user = auth()->user();
        $company = $user->currentCompany();

        $data = new AppointmentData(
            id: $this->appointment?->id,
            company_id: $company->id,
            calendar_id: $this->calendar_id,
            title: $this->title,
            type: AppointmentType::from($this->type),
            start_at: Carbon::parse($this->start_at),
            end_at: Carbon::parse($this->end_at),
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
    }

    #[Computed]
    public function availableCalendars()
    {
        $user = auth()->user();

        return Calendar::accessibleBy($user)
            ->where('company_id', $user->currentCompany()->id)
            ->get();
    }

    #[Computed]
    public function companyUsers()
    {
        return auth()->user()->currentCompany()->users;
    }
}; ?>

<flux:modal wire:model="showModal" class="max-w-2xl">
    <div class="p-6 space-y-6">
        <flux:heading size="lg">
            {{ $appointment ? __('calendar.edit_appointment') : __('calendar.new_appointment') }}
        </flux:heading>

        <form wire:submit="save" class="space-y-4">
            {{-- Takvim Seçimi --}}
            <flux:select wire:model="calendar_id" label="{{ __('calendar.calendar') }}" required>
                <option value="">{{ __('calendar.select_calendar') }}</option>
                @foreach($this->availableCalendars as $calendar)
                    <option value="{{ $calendar->id }}">{{ $calendar->name }}</option>
                @endforeach
            </flux:select>

            {{-- Başlık --}}
            <flux:input wire:model.blur="title" label="{{ __('calendar.appointment_title') }}"
                placeholder="{{ __('calendar.appointment_title_placeholder') }}" required />

            {{-- Tür --}}
            <flux:select wire:model="type" label="{{ __('calendar.appointment_type') }}" required>
                @foreach(\App\Enums\AppointmentType::cases() as $t)
                    <option value="{{ $t->value }}">{{ $t->label() }}</option>
                @endforeach
            </flux:select>

            {{-- Tarih/Saat --}}
            <div class="grid grid-cols-2 gap-4">
                <flux:input type="datetime-local" wire:model.blur="start_at" label="{{ __('calendar.start') }}"
                    required />
                <flux:input type="datetime-local" wire:model.blur="end_at" label="{{ __('calendar.end') }}" required />
            </div>

            {{-- Tüm gün --}}
            <flux:checkbox wire:model.live="all_day" label="{{ __('calendar.all_day') }}" />

            {{-- Konum --}}
            <flux:input wire:model.blur="location" label="{{ __('calendar.location') }}"
                placeholder="{{ __('calendar.location_placeholder') }}" />

            {{-- Katılımcılar --}}
            <div>
                <label class="block text-sm font-medium mb-2">{{ __('calendar.attendees') }}</label>
                <div class="flex flex-wrap gap-2 p-3 border rounded-lg dark:border-zinc-700 max-h-40 overflow-y-auto">
                    @foreach($this->companyUsers as $user)
                        <label @class([
                            'flex items-center gap-2 px-3 py-1.5 rounded-full cursor-pointer transition-colors text-sm',
                            'bg-primary-100 text-primary-700 dark:bg-primary-900/30 dark:text-primary-300' => in_array($user->id, $attendee_user_ids),
                            'bg-zinc-100 text-zinc-700 hover:bg-zinc-200 dark:bg-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-700' => !in_array($user->id, $attendee_user_ids),
                        ])>
                            <input type="checkbox" value="{{ $user->id }}" wire:model.live="attendee_user_ids"
                                class="sr-only">
                            {{ $user->name }}
                        </label>
                    @endforeach
                </div>
            </div>

            {{-- Açıklama --}}
            <flux:textarea wire:model.blur="description" label="{{ __('calendar.description') }}" rows="3" />

            {{-- Butonlar --}}
            <div class="flex justify-end gap-2 pt-4">
                <flux:button variant="ghost" type="button" wire:click="$set('showModal', false)">
                    {{ __('common.cancel') }}
                </flux:button>
                <flux:button type="submit" variant="primary">
                    {{ $appointment ? __('common.update') : __('common.create') }}
                </flux:button>
            </div>
        </form>
    </div>
</flux:modal>