<?php

use Livewire\Volt\Component;
use Livewire\Attributes\On;
use Livewire\Attributes\Computed;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use App\Services\CalendarService;

new class extends Component {
    public ?string $date = null; // Passed as 'Y-m-d'
    public bool $isOpen = false;

    // Triggered by event from index component
    #[On('open-day-detail')]
    public function open(string $date): void
    {
        $this->date = $date;
        $this->isOpen = true;
    }

    #[On('close-day-detail')]
    public function close(): void
    {
        $this->isOpen = false;
    }

    #[On('calendar-refresh')]
    public function refresh(): void
    {
        // Just re-render computed property
        unset($this->agenda);
    }

    #[Computed]
    public function currentDate(): ?Carbon
    {
        return $this->date ? Carbon::parse($this->date) : null;
    }

    #[Computed]
    public function agenda(): Collection
    {
        // currentCompany is a method, returns ?Company
        $company = auth()->user()->currentCompany();

        if (!$this->date || !$company) {
            return collect();
        }

        return app(CalendarService::class)->getDayAgenda(
            Carbon::parse($this->date),
            $company->id
        );
    }
}; ?>

<div x-data="{ show: @entangle('isOpen') }" 
    x-show="show" 
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0 scale-95" 
    x-transition:enter-end="opacity-100 scale-100"
    x-transition:leave="transition ease-in duration-100" 
    x-transition:leave-start="opacity-100 scale-100"
    x-transition:leave-end="opacity-0 scale-95" 
    wire:keydown.window.escape="close"
    class="absolute inset-0 z-20 bg-white dark:bg-zinc-800 flex flex-col"
    style="display: none;" {{-- Start hidden --}}>
    {{-- Sticky Header --}}
    <div
        class="flex items-center justify-between px-4 py-3 border-b border-zinc-200 dark:border-zinc-700 bg-white/95 dark:bg-zinc-800/95 backdrop-blur-sm sticky top-0 z-10">
        <div class="flex items-center gap-3">
            <flux:button variant="ghost" icon="arrow-left" wire:click="close">
                {{ __('common.back') }}
            </flux:button>
            <div>
                <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">
                    {{ $this->currentDate?->locale('tr')->translatedFormat('d F Y') }}
                </h3>
                <p class="text-xs text-zinc-500 dark:text-zinc-400">
                    {{ $this->currentDate?->locale('tr')->translatedFormat('l') }}
                </p>
            </div>
        </div>

        {{-- Add specific to this day --}}
        <flux:button variant="primary" icon="plus"
            wire:click="$dispatch('open-appointment-form', { prefillDate: '{{ $date }}' })">
            {{ __('common.add') }}
        </flux:button>
    </div>

    {{-- Content --}}
    <div class="flex-1 overflow-y-auto p-4">
        @if($this->agenda->isEmpty())
            <div class="h-full flex flex-col items-center justify-center text-zinc-400">
                <flux:icon name="calendar" class="size-12 mb-3 opacity-50" />
                <p>{{ __('calendar.no_events_today') }}</p>
                <flux:button variant="subtle" size="sm" class="mt-4"
                    wire:click="$dispatch('open-appointment-form', { prefillDate: '{{ $date }}' })">
                    {{ __('calendar.create_first_event') }}
                </flux:button>
            </div>
        @else
            {{-- Responsive Grid: Mobile Stack (cols-1), Desktop (cols-3) --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                @foreach($this->agenda as $item)
                    @if($item['type'] === 'event')
                        @php $event = $item['data']; @endphp
                        <div wire:key="agenda-event-{{ $event->id }}"
                            wire:click="$dispatch('open-appointment-form', { appointmentId: {{ $event->id }} })" class="relative group p-3 rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 hover:shadow-md transition cursor-pointer flex flex-col gap-1
                                                    @if($event->calendar?->color) border-l-4 @endif"
                            style="@if($event->calendar?->color) border-left-color: {{ $event->calendar->color }}; @endif">
                            <div class="flex justify-between items-start">
                                <span class="text-sm font-semibold text-zinc-900 dark:text-zinc-100 line-clamp-1">
                                    {{ $event->title }}
                                </span>
                                {{-- Type Icon --}}
                                <flux:icon :name="$event->type->icon()" class="size-4 text-zinc-400" />
                            </div>

                            <div class="text-xs text-zinc-500 dark:text-zinc-400 flex items-center gap-2 mt-1">
                                <flux:icon name="clock" class="size-3" />
                                {{ $event->start_at->format('H:i') }} - {{ $event->end_at->format('H:i') }}
                            </div>

                            @if($event->location)
                                <div class="text-xs text-zinc-500 dark:text-zinc-400 flex items-center gap-2">
                                    <flux:icon name="map-pin" class="size-3" />
                                    <span class="truncate">{{ $event->location }}</span>
                                </div>
                            @endif
                        </div>

                    @elseif($item['type'] === 'gap')
                        {{-- Ghost Gap Card --}}
                        <div wire:key="agenda-gap-{{ $item['start']->timestamp }}"
                            wire:click="$dispatch('open-appointment-form', { prefillDate: '{{ $date }}', start_time: '{{ $item['start']->format('H:i') }}' })"
                            class="p-3 rounded-lg border border-dashed border-zinc-300 dark:border-zinc-600 bg-zinc-50/50 dark:bg-zinc-900/30 
                                                                       hover:bg-primary-50 dark:hover:bg-primary-900/10 hover:border-primary-300 dark:hover:border-primary-700 
                                                                       transition cursor-pointer flex items-center justify-center min-h-[80px] group">
                            <div class="text-center group-hover:scale-105 transition">
                                <div
                                    class="text-xs font-medium text-zinc-500 dark:text-zinc-400 group-hover:text-primary-600 dark:group-hover:text-primary-400">
                                    {{ $item['start']->format('H:i') }} - {{ $item['end']->format('H:i') }}
                                </div>
                                <div class="text-[10px] text-zinc-400 mt-1">
                                    {{ $item['duration'] }}m {{ __('calendar.free_time') }}
                                </div>
                                <div
                                    class="text-xs font-medium text-primary-600 dark:text-primary-400 mt-1 opacity-0 group-hover:opacity-100 transition">
                                    {{ __('common.add') }}
                                </div>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        @endif
    </div>
</div>