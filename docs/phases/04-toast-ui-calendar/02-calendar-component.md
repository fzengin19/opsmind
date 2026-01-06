# Step 2: Calendar Component (The Core)

This is the brain of the operation. We use a Class-based Volt component to handle data fetching and state, while Alpine.js manages volume rendering.

## 🏛 Component Architecture

**Path:** `resources/views/livewire/calendar/index.blade.php`

### 1. The PHP Logic (Class-based Volt)

```php
<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Computed;
use App\Models\Appointment;
use Carbon\Carbon;

new #[Layout('components.layouts.app')] class extends Component {
    // --- State ---
    public string $currentView = 'week'; 
    public string $currentDate; // ISO string 2023-01-01
    
    // --- Lifecycle ---
    public function mount()
    {
        $this->currentDate = now()->toIso8601String();
    }

    // --- API Methods (Called by Alpine) ---
    
    /**
     * Fetch events for a specific range.
     * Alpine calls this via $wire.fetchEvents().
     */
    public function fetchEvents(string $startStr, string $endStr): array
    {
        // 1. Parse Dates (Always assume UTC input from library or handle conversion)
        // TOAST UI usually sends local browser times if timezone is set, 
        // but we want to query DB in UTC.
        
        $user = auth()->user();
        $tz = $user->timezone ?? 'UTC';

        // Parse inputs as User's Timezone, then convert to UTC for DB
        $start = Carbon::parse($startStr, $tz)->setTimezone('UTC');
        $end = Carbon::parse($endStr, $tz)->setTimezone('UTC');

        // 2. Query
        $appointments = Appointment::query()
            ->with(['attendees', 'company'])
            ->where('company_id', $user->company_id)
            ->where(function($q) use ($start, $end) {
                $q->whereBetween('start_at', [$start, $end])
                  ->orWhereBetween('end_at', [$start, $end]);
            })
            ->get();

        // 3. Transformation (DTO-like array)
        return $appointments->map(function ($appt) use ($tz) {
            return [
                'id' => (string) $appt->id,
                'calendarId' => $appt->type->value,
                'title' => $appt->title,
                'body' => $appt->description, // Optional, maybe too heavy?
                'category' => $appt->all_day ? 'allday' : 'time',
                'location' => $appt->location,
                
                // Return dates in ISO8601
                'start' => $appt->start_at->toIso8601String(),
                'end' => $appt->end_at->toIso8601String(),
                
                // Styling
                'color' => '#ffffff', // Text color
                'backgroundColor' => $appt->color ?? $appt->type->color(),
                'borderColor' => $appt->color ?? $appt->type->color(),
                'isReadOnly' => false, // TODO: Implement Policy check
            ];
        })->values()->toArray();
    }

    // --- Actions ---
    
    public function updateViewMode($mode)
    {
        $this->currentView = $mode;
        // Allows us to persist user preference if needed
    }
};
?>
```

### 2. The View (Blade + Alpine + Flux)

```blade
<div 
    class="flex flex-col h-full w-full gap-4"
    x-data="calendarApp"
>
    <!-- Header / Toolbar -->
    <div class="flex flex-col sm:flex-row justify-between items-center gap-4 bg-white dark:bg-zinc-900 p-4 rounded-xl border border-zinc-200 dark:border-zinc-800 shadow-sm">
        
        <!-- Left: Navigation -->
        <div class="flex items-center gap-2">
            <flux:button icon="chevron-left" variant="ghost" size="sm" @click="prev()" />
            <flux:button icon="chevron-right" variant="ghost" size="sm" @click="next()" />
            <flux:button variant="subtle" size="sm" @click="today()">Bugün</flux:button>
            
            <h2 class="ml-4 text-lg font-semibold text-zinc-800 dark:text-zinc-100" x-text="title">
                <!-- Title populated by JS -->
            </h2>
        </div>

        <!-- Right: View Switcher -->
        <div class="flex items-center gap-2">
            <flux:select x-model="currentView" @change="changeView($event.target.value)" variant="filled" size="sm">
                <option value="month">Ay</option>
                <option value="week">Hafta</option>
                <option value="day">Gün</option>
                <!-- NOTE: Agenda switch handled differently -->
            </flux:select>
        </div>
    </div>

    <!-- Calendar Container -->
    <!-- wire:ignore is CRITICAL here -->
    <div wire:ignore class="flex-1 bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 shadow-sm p-1 overflow-hidden relative min-h-[600px]">
        <div id="calendar" class="h-full"></div>
    </div>
</div>

@script
<script>
    Alpine.data('calendarApp', () => ({
        instance: null,
        currentView: @entangle('currentView'),
        title: '',
        
        init() {
            // Instantiate using our Manager Class
            // We pass the raw DOM element
            this.instance = new window.CalendarManager(document.getElementById('calendar'), {
                defaultView: this.currentView,
                timezone: {
                    zones: [
                        { timezoneName: '{{ auth()->user()->timezone ?? "UTC" }}', displayLabel: 'Local' }
                    ]
                },
                // Templates for rendering
                template: {
                    time: function(event) {
                        return `<span class="truncate pl-1 font-medium">${event.title}</span>`;
                    }
                }
            });

            // Initialize Manager
            const calendar = this.instance.init();

            // Bind Events
            this.instance.onUpdate = (e)  => this.handleUpdate(e);
            this.instance.onSelect = (e)  => this.handleSelect(e);
            this.instance.onClick  = (e)  => this.handleClick(e);

            // Initial Fetch
            this.fetchData();
        },

        fetchData() {
            const range = this.instance.getDateRange();
            
            // Show loading indicator logic can go here
            
            this.$wire.fetchEvents(range.start.toISOString(), range.end.toISOString())
                .then(events => {
                    this.instance.updateEvents(events);
                    this.updateTitle();
                });
        },

        // Navigation Wrappers
        next() { this.instance.next(); this.fetchData(); },
        prev() { this.instance.prev(); this.fetchData(); },
        today() { this.instance.today(); this.fetchData(); },
        
        changeView(view) {
            this.instance.changeView(view);
            this.updateTitle();
            this.fetchData();
        },

        updateTitle() {
            // Logic to format title based on view type and date
            // TOAST UI doesn't give a formatted title string automatically, 
            // implemented via options.template logic or manual date formatting here.
             const start = this.instance.instance.getDateRangeStart();
             const end = this.instance.instance.getDateRangeEnd();
             // Simple formatter using Intl.DateTimeFormat
             this.title = `${start.toDate().toLocaleDateString()} - ${end.toDate().toLocaleDateString()}`;
        },

        // Event Handlers
        handleSelect(e) {
            // Trigger Create Modal
            this.$dispatch('open-create-modal', { 
                start: e.start.toDate().toISOString(), 
                end: e.end.toDate().toISOString(),
                allDay: e.isAllday
            });
            calendar.clearGridSelections();
        },

        handleClick(e) {
             this.$dispatch('open-detail-modal', { id: e.event.id });
        },
        
        handleUpdate(e) {
            // Optimistic UI Update handled by TOAST UI visually
            // We just need to persist
            
            this.$wire.updateAppointmentDate(e.event.id, e.changes.start, e.changes.end)
                .catch(err => {
                    // Revert if failed
                     this.instance.instance.updateEvent(e.event.id, e.event); // Revert logic
                     // Show Toast Error
                });
        }
    }));
</script>
@endscript
```

## ✅ Checklist
- [ ] Ensure `CalendarManager` is imported correctly in `app.js`.
- [ ] Verify `AppointmentType::color()` exists and works.
- [ ] Check `fetchEvents` date range logic (UTC conversion).
- [ ] Test Navigation buttons causing data refresh.
