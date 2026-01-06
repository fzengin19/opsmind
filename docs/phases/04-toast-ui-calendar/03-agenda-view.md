# Step 3: Agenda View (Mobile & List Mode)

Since TOAST UI lacks a list view, and mobile grid views are often unusable, we implement a dedicated Agenda View.

## 📱 Strategy

- **Mobile First:** On mobile screens, this view should likely be the *default*.
- **Shared Data:** It consumes the same `Appointment` data but visualizes it differently.
- **Flux UI:** We leverage `flux:table` or a custom list stack.

## 🧩 Component Implementation

**File:** `resources/views/livewire/calendar/agenda.blade.php`

```php
<?php

use Livewire\Volt\Component;
use App\Models\Appointment;
use Carbon\Carbon;

new class extends Component {
    public $startDate;
    public $range = 14; // Default 2 weeks

    public function with()
    {
        $start = Carbon::parse($this->startDate ?? now());
        $end = $start->copy()->addDays($this->range);

        $appointments = Appointment::query()
            ->where('company_id', auth()->user()->company_id)
            ->whereBetween('start_at', [$start, $end])
            ->orderBy('start_at')
            ->get()
            ->groupBy(fn($appt) => $appt->start_at->translatedFormat('l, d F Y'));

        return [
            'groupedAppointments' => $appointments
        ];
    }
};
?>
```

## 🎨 UI Template (Blade)

```blade
<div class="flex flex-col gap-6 w-full max-w-3xl mx-auto">
    
    @foreach($groupedAppointments as $dateString => $events)
        <section class="flex flex-col gap-2">
            <!-- Sticky Header for Date -->
            <div class="sticky top-0 z-10 bg-zinc-50 dark:bg-zinc-950 py-2 border-b border-zinc-200 dark:border-zinc-800">
                <h3 class="text-sm font-bold text-zinc-500 uppercase tracking-widest pl-1">
                    {{ $dateString }}
                </h3>
            </div>

            <div class="flex flex-col gap-2">
                @foreach($events as $event)
                    <div 
                        wire:click="$dispatch('open-detail-modal', { id: '{{ $event->id }}' })"
                        class="group flex items-start gap-4 p-4 rounded-xl bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 hover:border-indigo-500 dark:hover:border-indigo-500 transition-all cursor-pointer shadow-sm"
                    >
                        <!-- Left Stripe (Color) -->
                        <div class="w-1.5 self-stretch rounded-full" style="background-color: {{ $event->type->color() }}"></div>
                        
                        <!-- Time Column -->
                        <div class="flex flex-col items-center min-w-[3rem]">
                            @if($event->all_day)
                                <span class="text-xs font-bold text-zinc-500">TÜM<br>GÜN</span>
                            @else
                                <span class="text-sm font-semibold text-zinc-800 dark:text-zinc-200">{{ $event->start_at->format('H:i') }}</span>
                                <span class="text-xs text-zinc-400">{{ $event->end_at->format('H:i') }}</span>
                            @endif
                        </div>

                        <!-- Content -->
                        <div class="flex-1 min-w-0">
                            <h4 class="text-base font-semibold text-zinc-900 dark:text-zinc-100 truncate group-hover:text-indigo-600 dark:group-hover:text-indigo-400">
                                {{ $event->title }}
                            </h4>
                            
                            <!-- Location / Attendees Snippet -->
                            <div class="flex items-center gap-3 mt-1 text-xs text-zinc-500">
                                @if($event->location)
                                    <div class="flex items-center gap-1">
                                        <flux:icon name="map-pin" class="size-3" />
                                        <span>{{ $event->location }}</span>
                                    </div>
                                @endif
                                
                                <span class="px-1.5 py-0.5 rounded bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400">
                                    {{ $event->type->label() }}
                                </span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @endforeach

    @if($groupedAppointments->isEmpty())
        <div class="flex flex-col items-center justify-center py-12 text-zinc-500">
            <flux:icon name="calendar" class="size-12 mb-4 opacity-50" />
            <p>Bu tarih aralığında planlanmış etkinlik yok.</p>
        </div>
    @endif
</div>
```

## ✅ Integration
In the main `index.blade.php`:

```blade
@if($currentView === 'agenda')
    <div class="bg-zinc-50 dark:bg-zinc-950 p-4 rounded-xl border border-zinc-200 dark:border-zinc-800 min-h-[600px]">
        <livewire:calendar.agenda :start-date="$currentDate" />
    </div>
@else
    <!-- TOAST UI Container -->
@endif
```
