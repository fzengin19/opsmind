<?php

use App\Models\Appointment;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component
{
    public string $currentView = 'week';

    public function mount(): void
    {
        // Could load user preference here
    }

    /**
     * Fetch events for a specific date range.
     * Called by Alpine.js via $wire.fetchEvents().
     */
    public function fetchEvents(string $startStr, string $endStr): array
    {
        $user = auth()->user();
        $tz = $user->timezone ?? 'UTC';

        $start = Carbon::parse($startStr)->setTimezone('UTC');
        $end = Carbon::parse($endStr)->setTimezone('UTC');

        $appointments = Appointment::query()
            ->where('company_id', $user->currentCompany()?->id)
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('start_at', [$start, $end])
                    ->orWhereBetween('end_at', [$start, $end]);
            })
            ->get();

        return $appointments->map(function ($appt) {
            return [
                'id' => (string) $appt->id,
                'calendarId' => $appt->type->value,
                'title' => $appt->title,
                'category' => $appt->all_day ? 'allday' : 'time',
                'location' => $appt->location,
                'start' => $appt->start_at->toIso8601String(),
                'end' => $appt->end_at->toIso8601String(),
                'color' => '#ffffff',
                'backgroundColor' => $appt->color ?? $appt->type->color(),
                'borderColor' => $appt->color ?? $appt->type->color(),
                'isReadOnly' => false,
            ];
        })->values()->toArray();
    }

    public function updateViewMode(string $mode): void
    {
        $this->currentView = $mode;
    }
}; ?>

<div class="flex flex-col gap-6" x-data="calendarApp">
    {{-- Page Header --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl">{{ __('calendar.title') }}</flux:heading>
            <flux:subheading class="text-zinc-500">{{ __('calendar.subheading') }}</flux:subheading>
        </div>
        <div class="flex items-center gap-2">
            {{-- Future: Add appointment button --}}
        </div>
    </div>

    {{-- Toolbar --}}
    <div class="flex flex-col sm:flex-row justify-between items-center gap-4 bg-white dark:bg-zinc-900 p-4 rounded-xl border border-zinc-200 dark:border-zinc-800 shadow-sm">
        {{-- Left: Navigation --}}
        <div class="flex items-center gap-2">
            <flux:button icon="chevron-left" variant="ghost" size="sm" @click="prev()" />
            <flux:button icon="chevron-right" variant="ghost" size="sm" @click="next()" />
            <flux:button variant="subtle" size="sm" @click="today()">{{ __('calendar.buttons.today') }}</flux:button>

            <h2 class="ml-4 text-lg font-semibold text-zinc-800 dark:text-zinc-100" x-text="title"></h2>
        </div>

        {{-- Right: View Switcher --}}
        <div class="flex items-center gap-2">
            <flux:select x-model="currentView" @change="changeView($event.target.value)" size="sm">
                <option value="month">{{ __('calendar.view_modes.month') }}</option>
                <option value="week">{{ __('calendar.view_modes.week') }}</option>
                <option value="day">{{ __('calendar.view_modes.day') }}</option>
            </flux:select>
        </div>
    </div>

    {{-- Calendar Container --}}
    {{-- wire:ignore prevents Livewire from touching TOAST UI's DOM --}}
    <div wire:ignore class="bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 shadow-sm overflow-hidden">
        {{-- CRITICAL: TOAST UI requires explicit inline height per official docs --}}
        <div id="calendar" style="height: 600px;"></div>
    </div>
</div>

@script
<script>
    Alpine.data('calendarApp', () => ({
        instance: null,
        currentView: @entangle('currentView'),
        title: '',

        init() {
            const container = document.getElementById('calendar');
            
            // Calculate dynamic height based on viewport
            const viewportHeight = window.innerHeight;
            const offset = 280; // Header + toolbar + margins
            const calculatedHeight = Math.max(viewportHeight - offset, 500);
            container.style.height = `${calculatedHeight}px`;

            // Create CalendarManager instance
            this.instance = new window.CalendarManager(container, {
                defaultView: this.currentView,
                timezone: {
                    zones: [
                        { timezoneName: '{{ auth()->user()->timezone ?? "UTC" }}', displayLabel: 'Local' }
                    ]
                }
            });

            // Initialize
            this.instance.init();

            // Bind event callbacks
            this.instance.onUpdate = (e) => this.handleUpdate(e);
            this.instance.onSelect = (e) => this.handleSelect(e);
            this.instance.onClick = (e) => this.handleClick(e);

            // Fetch initial data
            this.fetchData();
            
            // Handle window resize
            window.addEventListener('resize', () => {
                const newHeight = Math.max(window.innerHeight - offset, 500);
                container.style.height = `${newHeight}px`;
                this.instance.instance.render();
            });
        },

        fetchData() {
            const range = this.instance.getDateRange();
            this.$wire.fetchEvents(range.start.toISOString(), range.end.toISOString())
                .then(events => {
                    this.instance.updateEvents(events);
                    this.updateTitle();
                });
        },

        next() { this.instance.next(); this.fetchData(); },
        prev() { this.instance.prev(); this.fetchData(); },
        today() { this.instance.today(); this.fetchData(); },

        changeView(view) {
            this.instance.changeView(view);
            this.updateTitle();
            this.fetchData();
        },

        updateTitle() {
            const start = this.instance.instance.getDateRangeStart();
            const options = { year: 'numeric', month: 'long' };
            this.title = start.toDate().toLocaleDateString('tr-TR', options);
        },

        handleSelect(e) {
            this.$dispatch('open-create-modal', {
                start: e.start.toDate().toISOString(),
                end: e.end.toDate().toISOString(),
                allDay: e.isAllday
            });
            this.instance.instance.clearGridSelections();
        },

        handleClick(e) {
            this.$dispatch('open-detail-modal', { id: e.event.id });
        },

        handleUpdate(e) {
            console.log('Event updated:', e.event.id, e.changes);
        }
    }));
</script>
@endscript