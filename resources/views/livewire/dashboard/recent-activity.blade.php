<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Lazy;

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
}; ?>

<div class="bg-white dark:bg-zinc-800 rounded-xl p-6 shadow-sm border border-zinc-200 dark:border-zinc-700">
    <flux:heading size="sm" class="mb-4 flex items-center gap-2">
        <flux:icon name="clock" class="size-5 text-warning" />
        {{ __('dashboard.recent_activity') }}
    </flux:heading>

    <flux:text class="text-zinc-500">
        {{ __('dashboard.coming_soon') }}
    </flux:text>
</div>