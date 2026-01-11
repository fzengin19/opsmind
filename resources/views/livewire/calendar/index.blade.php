<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Carbon\Carbon;
use App\Services\CalendarService;
use App\Models\Calendar;


new #[Layout('components.layouts.app')] class extends Component {
    #[Url]
    public string $view = 'month';

    #[Url(history: true, keep: true)]
    public string $date = '';

    protected function queryString()
    {
        return [
            'date' => [
                'except' => '',
                'history' => true,
                'keep' => true,
            ],
            'view' => [
                'except' => 'month',
            ],
        ];
    }

    public array $visibleCalendarIds = [];

    public function mount(): void
    {
        if (empty($this->date)) {
            $this->date = now()->toDateString();
        }

        // Başlangıçta tüm erişilebilir takvimler görünür
        $this->visibleCalendarIds = $this->accessibleCalendars->pluck('id')->toArray();
    }

    public function getCurrentDate(): Carbon
    {
        return Carbon::parse($this->date);
    }

    public function next(): void
    {
        $date = $this->getCurrentDate()->copy();
        match ($this->view) {
            'month' => $date->addMonth(),
            'week' => $date->addWeek(),
            'day' => $date->addDay(),
        };
        $this->date = $date->toDateString();
    }

    public function prev(): void
    {
        $date = $this->getCurrentDate()->copy();
        match ($this->view) {
            'month' => $date->subMonth(),
            'week' => $date->subWeek(),
            'day' => $date->subDay(),
        };
        $this->date = $date->toDateString();
    }

    public function today(): void
    {
        $this->date = now()->toDateString();
    }

    public function setView(string $view): void
    {
        $this->view = $view;
    }

    public function with(CalendarService $service): array
    {
        $days = match ($this->view) {
            'month' => $service->getMonthGrid($this->getCurrentDate()),
            'week', 'day' => $service->getWeekGrid($this->getCurrentDate()),
            default => [],
        };

        $timeSlots = in_array($this->view, ['week', 'day'])
            ? $service->getTimeSlots()
            : [];

        $company = auth()->user()?->currentCompany();
        
        // Get appointments from database
        $events = [];
        $monthEvents = [];
        
        if ($company) {
            if (in_array($this->view, ['week', 'day'])) {
                $events = $service->getAppointmentsForWeek($this->getCurrentDate(), $company->id)->toArray();
            }
            if ($this->view === 'month') {
                $monthEvents = $service->getAppointmentsForMonth($this->getCurrentDate(), $company->id);
            }
        }

        return compact('days', 'timeSlots', 'events', 'monthEvents');
    }

    #[Computed]
    public function accessibleCalendars()
    {
        $user = auth()->user();
        if (! $user || ! $user->currentCompany()) {
            return collect();
        }

        return Calendar::accessibleBy($user)
            ->where('company_id', $user->currentCompany()->id)
            ->get();
    }

    public function toggleCalendar(int $calendarId): void
    {
        if (in_array($calendarId, $this->visibleCalendarIds)) {
            $this->visibleCalendarIds = array_values(array_diff($this->visibleCalendarIds, [$calendarId]));
        } else {
            $this->visibleCalendarIds[] = $calendarId;
        }
    }

    public function openNewAppointment(?string $date = null): void
    {
        $this->dispatch('open-appointment-form', appointmentId: null, prefillDate: $date);
    }

    public function openEditAppointment(int $appointmentId): void
    {
        $this->dispatch('open-appointment-form', appointmentId: $appointmentId, prefillDate: null);
    }

    public function confirmDeleteAppointment(int $appointmentId): void
    {
        $this->dispatch('confirm-delete-appointment', appointmentId: $appointmentId);
    }

    public function selectDay(string $date): void
    {
        $this->dispatch('open-day-detail', date: $date);
    }

    #[On('calendar-refresh')]
    public function refreshCalendar(): void
    {
        // Livewire will re-render automatically
    }
}; ?>



<div class="flex flex-col gap-6">

    {{-- Page Header with Toolbar (Design System 2.1) --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

        {{-- Left: Title + Navigation --}}
        <div class="flex items-center gap-4">
            {{-- Dynamic Title --}}
            <flux:heading size="xl">
                @if($view === 'month')
                    {{ $this->getCurrentDate()->locale('tr')->translatedFormat('F Y') }}
                @elseif($view === 'week')
                    {{ $this->getCurrentDate()->copy()->startOfWeek()->translatedFormat('d') }} -
                    {{ $this->getCurrentDate()->copy()->endOfWeek()->translatedFormat('d M Y') }}
                @else
                    {{ $this->getCurrentDate()->locale('tr')->translatedFormat('d F Y') }}
                @endif
            </flux:heading>

            {{-- Navigation Buttons (Design System 4.1 - ghost variant) --}}
            <div class="flex items-center gap-1">
                <flux:button variant="ghost" size="sm" icon="chevron-left" wire:click="prev" />
                <flux:button variant="ghost" size="sm" wire:click="today">{{ __('calendar.today') }}</flux:button>
                <flux:button variant="ghost" size="sm" icon="chevron-right" wire:click="next" />
            </div>
        </div>

        {{-- Right: View Switcher + New Appointment Button --}}
        <div class="flex items-center gap-3">
                <flux:button variant="primary" size="sm" class="cursor-default">
                    {{ __('calendar.month') }}
                </flux:button>
                <!--
                <flux:button :variant="$view === 'week' ? 'primary' : 'ghost'" size="sm" wire:click="setView('week')">
                    {{ __('calendar.week') }}
                </flux:button>
                <flux:button :variant="$view === 'day' ? 'primary' : 'ghost'" size="sm" wire:click="setView('day')">
                    {{ __('calendar.day') }}
                </flux:button>
                -->

            <flux:button variant="primary" icon="plus" wire:click="openNewAppointment">
                {{ __('calendar.new_appointment') }}
            </flux:button>
        </div>

    </div>


    {{-- Calendar Grid Container --}}
    <div
        class="bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-xl shadow-sm overflow-hidden relative">

        @if($view === 'month')
            {{-- Day Headers --}}
            <div class="grid grid-cols-7 border-b border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-900/50">
                @foreach(['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'] as $dayKey)
                    <div
                        class="py-2 sm:py-3 text-center text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                        {{ __('calendar.days_short.' . $dayKey) }}
                    </div>
                @endforeach
            </div>

            {{-- Month Grid --}}
            <div class="grid grid-cols-7">
                @foreach($days as $day)
                    <div wire:key="{{ $day['date']->toDateString() }}" class="relative min-h-[80px] sm:min-h-[100px] p-1 sm:p-2 border-b border-r border-zinc-200 dark:border-zinc-700 transition
                                {{ $day['isCurrentMonth']
                    ? 'bg-white dark:bg-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-700/30'
                    : 'bg-zinc-50/50 dark:bg-zinc-900/30' }}">

                        {{-- Day Number --}}
                        <span class="inline-flex items-center justify-center text-xs sm:text-sm font-medium
                                    {{ $day['isToday']
                    ? 'size-6 sm:size-7 bg-primary-500 text-white rounded-full'
                    : ($day['isCurrentMonth']
                        ? 'text-zinc-900 dark:text-zinc-100'
                        : 'text-zinc-400 dark:text-zinc-500') }}">
                            {{ $day['day'] }}
                        </span>


                        {{-- Event Chips --}}
                        @php
                            $dateKey = $day['date']->toDateString();
                            $dayEvents = $monthEvents[$dateKey] ?? [];
                        @endphp

                        {{-- Day Click Area (Entire Cell) - BACKEND DISPATCH for safety --}}
                        <div 
                            wire:click="selectDay('{{ $day['date']->toDateString() }}')"
                            class="absolute inset-0 z-0 cursor-pointer"
                        ></div>

                        @if(count($dayEvents) > 0)
                            <div class="mt-1 space-y-0.5 relative z-10 pointer-events-none">
                                @foreach(array_slice($dayEvents, 0, 3) as $event)
                                    <div wire:key="event-{{ $event['id'] }}" class="text-[10px] truncate px-1 py-0.5 rounded cursor-pointer transition
                                        @switch($event['color'])
                                            @case('primary')
                                                bg-primary-100 text-primary-700 dark:bg-primary-900/40 dark:text-primary-300
                                                @break
                                            @case('success')
                                                bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300
                                                @break
                                            @case('warning')
                                                bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300
                                                @break
                                            @default
                                                bg-zinc-100 text-zinc-700 dark:bg-zinc-700 dark:text-zinc-300
                                        @endswitch">
                                        {{ $event['title'] }}
                                    </div>
                                @endforeach
                                @if(count($dayEvents) > 3)
                                    <div class="text-[10px] text-zinc-500 dark:text-zinc-400 px-1">
                                        +{{ count($dayEvents) - 3 }} {{ __('calendar.more') }}
                                    </div>
                                @endif
                            </div>
                        @endif

                    </div>

                @endforeach
            </div>
        @elseif($view === 'week')
            {{-- Week View Container (single scroll container) --}}
            <div class="h-[500px] sm:h-[600px] lg:h-[750px] overflow-y-auto" x-init="$el.scrollTop = 8 * 60">
                
                {{-- Header Row (sticky inside scroll container) --}}
                <div class="flex sticky top-0 z-30 border-b border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-900">
                    {{-- Time Column Header (Empty Corner) --}}
                    <div class="w-14 sm:w-16 flex-shrink-0 border-r border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-900"></div>
                    
                    {{-- Day Headers --}}
                    @foreach($days as $day)
                        <div class="flex-1 py-2 sm:py-3 text-center border-r border-zinc-200 dark:border-zinc-700 last:border-r-0 bg-zinc-50 dark:bg-zinc-900">
                            <div class="text-xs text-zinc-500 dark:text-zinc-400 uppercase">
                                {{ $day['dayName'] }}
                            </div>
                            <div class="text-base sm:text-lg font-semibold {{ $day['isToday'] ? 'text-primary-600 dark:text-primary-400' : 'text-zinc-900 dark:text-zinc-100' }}">
                                {{ $day['day'] }}
                            </div>
                        </div>
                    @endforeach
                </div>
                
                {{-- Body Row (Time Column + Day Columns) --}}
                <div class="flex">
                    {{-- Time Column --}}
                    <div class="w-14 sm:w-16 flex-shrink-0 border-r border-zinc-200 dark:border-zinc-700 bg-zinc-50/50 dark:bg-zinc-900/20">
                        @foreach($timeSlots as $slot)
                            <div class="h-[60px] flex items-center justify-center border-b border-dotted border-zinc-200 dark:border-zinc-700">
                                <span class="text-[10px] sm:text-xs text-zinc-400 dark:text-zinc-500">
                                    {{ $slot }}
                                </span>
                            </div>
                        @endforeach

                    </div>
                    
                    {{-- Day Columns --}}
                    @foreach($days as $day)
                        <div class="flex-1 border-r border-zinc-100 dark:border-zinc-700/50 last:border-r-0 relative">
                            @foreach($timeSlots as $slot)
                                <div class="h-[60px] border-b border-dotted border-zinc-100 dark:border-zinc-700/50 hover:bg-zinc-50/50 dark:hover:bg-zinc-700/20 transition"></div>
                            @endforeach
                            
                            {{-- Events --}}
                            @foreach($events as $event)
                                @if($event['dayIndex'] === $loop->parent->index)
                                    @php
                                        $top = ($event['startHour'] * 60) + $event['startMinute'];
                                        $height = max(30, $event['durationMinutes']);
                                    @endphp
                                    <div 
                                        class="absolute inset-x-1 z-10 rounded-lg px-2 py-1 text-xs font-medium overflow-hidden cursor-pointer transition-all border hover:opacity-80
                                        @switch($event['color'])
                                            @case('primary')
                                                bg-primary-50 border-primary-200 text-primary-700 dark:bg-primary-900/30 dark:border-primary-700 dark:text-primary-300
                                                @break
                                            @case('success')
                                                bg-emerald-50 border-emerald-200 text-emerald-700 dark:bg-emerald-900/30 dark:border-emerald-700 dark:text-emerald-300
                                                @break
                                            @case('warning')
                                                bg-amber-50 border-amber-200 text-amber-700 dark:bg-amber-900/30 dark:border-amber-700 dark:text-amber-300
                                                @break
                                            @case('danger')
                                                bg-rose-50 border-rose-200 text-rose-700 dark:bg-rose-900/30 dark:border-rose-700 dark:text-rose-300
                                                @break
                                            @default
                                                bg-zinc-50 border-zinc-200 text-zinc-700 dark:bg-zinc-800 dark:border-zinc-600 dark:text-zinc-300
                                        @endswitch"
                                        style="top: {{ $top }}px; height: {{ $height }}px;">
                                        <span class="line-clamp-2">{{ $event['title'] }}</span>
                                    </div>


                                @endif
                            @endforeach
                        </div>
                    @endforeach

                </div>
                
            </div>


        @elseif($view === 'day')
            {{-- Day View Container (single scroll container) --}}
            <div class="h-[500px] sm:h-[600px] lg:h-[750px] overflow-y-auto" x-init="$el.scrollTop = 8 * 60">
                
                {{-- Header Row (sticky) --}}
                <div class="flex sticky top-0 z-30 border-b border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-900">
                    {{-- Time Column Header (Empty Corner) --}}
                    <div class="w-14 sm:w-16 flex-shrink-0 border-r border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-900"></div>
                    
                    {{-- Single Day Header --}}
                    <div class="flex-1 py-3 sm:py-4 text-center bg-zinc-50 dark:bg-zinc-900">
                        <div class="text-sm text-zinc-500 dark:text-zinc-400">
                            {{ $this->getCurrentDate()->locale('tr')->translatedFormat('l') }}
                        </div>
                        <div class="text-2xl sm:text-3xl font-bold {{ $this->getCurrentDate()->isToday() ? 'text-primary-600 dark:text-primary-400' : 'text-zinc-900 dark:text-zinc-100' }}">
                            {{ $this->getCurrentDate()->day }}
                        </div>
                        <div class="text-xs text-zinc-400 dark:text-zinc-500">
                            {{ $this->getCurrentDate()->locale('tr')->translatedFormat('F Y') }}
                        </div>
                    </div>
                </div>
                
                {{-- Body Row (Time Column + Day Column) --}}
                <div class="flex">
                    {{-- Time Column --}}
                    <div class="w-14 sm:w-16 flex-shrink-0 border-r border-zinc-200 dark:border-zinc-700 bg-zinc-50/50 dark:bg-zinc-900/20">
                        @foreach($timeSlots as $slot)
                            <div class="h-[60px] flex items-center justify-center border-b border-dotted border-zinc-200 dark:border-zinc-700">
                                <span class="text-[10px] sm:text-xs text-zinc-400 dark:text-zinc-500">
                                    {{ $slot }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                    
                    {{-- Single Day Column --}}
                    <div class="flex-1 relative">
                        @foreach($timeSlots as $slot)
                            <div class="h-[60px] border-b border-dotted border-zinc-100 dark:border-zinc-700/50 hover:bg-zinc-50/50 dark:hover:bg-zinc-700/20 transition"></div>
                        @endforeach
                        
                        {{-- Events (filter by current day's weekday) --}}
                        @php
                            $currentDayIndex = $this->getCurrentDate()->dayOfWeekIso - 1;
                        @endphp
                        @foreach($events as $event)
                            @if($event['dayIndex'] === $currentDayIndex)
                                @php
                                    $top = ($event['startHour'] * 60) + $event['startMinute'];
                                    $height = max(30, $event['durationMinutes']);
                                    
                                    // Hex color handling
                                    $hex = $event['color'] ?? '#3b82f6';
                                    
                                    // Convert hex to rgb for opacity
                                    $hex = ltrim($hex, '#');
                                    if(strlen($hex) == 3) {
                                        $r = hexdec(substr($hex,0,1).substr($hex,0,1));
                                        $g = hexdec(substr($hex,1,1).substr($hex,1,1));
                                        $b = hexdec(substr($hex,2,1).substr($hex,2,1));
                                    } else {
                                        $r = hexdec(substr($hex,0,2));
                                        $g = hexdec(substr($hex,2,2));
                                        $b = hexdec(substr($hex,4,2));
                                    }
                                    $bg = "rgba($r, $g, $b, 0.15)";
                                    $border = "rgba($r, $g, $b, 0.5)";
                                    $text = "rgb($r, $g, $b)"; // or standard text color
                                @endphp
                                <div 
                                    wire:click="$dispatch('open-appointment-form', { appointmentId: {{ $event['id'] }} })"
                                    class="absolute z-10 rounded-lg px-2 py-1 text-xs font-medium overflow-hidden cursor-pointer transition-all border hover:z-20 hover:shadow-md"
                                    style="
                                        top: {{ $top }}px; 
                                        height: {{ $height }}px; 
                                        left: {{ $event['left'] }}%; 
                                        width: {{ $event['width'] }}%;
                                        background-color: {{ $bg }};
                                        border-color: {{ $border }};
                                        color: {{ $text }};
                                    ">
                                    <span class="line-clamp-3">{{ $event['title'] }}</span>
                                </div>


                            @endif
                        @endforeach
                    </div>
                </div>
                
            </div>
        @endif
        
        {{-- Day Detail Overlay --}}
        <livewire:calendar.day-detail />

    </div>

    {{-- Modals --}}
    <livewire:calendar.appointment-form :calendars="$this->accessibleCalendars" />
    <livewire:calendar.delete-confirmation />

</div>