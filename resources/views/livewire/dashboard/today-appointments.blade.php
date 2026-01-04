<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Lazy;
use App\Models\Appointment;

new #[Lazy] class extends Component {
    public function placeholder(): string
    {
        return <<<'HTML'
        <div class="bg-white dark:bg-zinc-800 rounded-xl p-6 shadow-sm border border-zinc-200 dark:border-zinc-700">
            <div class="animate-pulse space-y-3">
                <div class="h-4 bg-zinc-200 dark:bg-zinc-700 rounded w-1/2"></div>
                <div class="h-3 bg-zinc-200 dark:bg-zinc-700 rounded"></div>
                <div class="h-3 bg-zinc-200 dark:bg-zinc-700 rounded w-3/4"></div>
            </div>
        </div>
        HTML;
    }

    public function with(): array
    {
        $company = auth()->user()->currentCompany();

        if (!$company) {
            return ['appointments' => collect()];
        }

        return [
            'appointments' => Appointment::where('company_id', $company->id)
                ->whereDate('start_at', today())
                ->orderBy('start_at')
                ->limit(5)
                ->get(),
        ];
    }
}; ?>

<div class="bg-white dark:bg-zinc-800 rounded-xl p-6 shadow-sm border border-zinc-200 dark:border-zinc-700">
    <flux:heading size="sm" class="mb-4 flex items-center gap-2">
        <flux:icon name="calendar" class="size-5 text-brand-500" />
        {{ __('dashboard.today_appointments') }}
    </flux:heading>

    @if($appointments->isEmpty())
        <flux:text class="text-zinc-500">
            {{ __('dashboard.no_appointments') }}
        </flux:text>
    @else
        <div class="space-y-3">
            @foreach($appointments as $apt)
                <div class="flex items-center gap-3 p-2 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700/50">
                    <div class="text-sm">
                        <div class="font-medium">{{ $apt->title }}</div>
                        <div class="text-zinc-500">{{ $apt->start_at->format('H:i') }}</div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>