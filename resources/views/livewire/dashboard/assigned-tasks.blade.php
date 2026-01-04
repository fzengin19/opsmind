<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Lazy;
use App\Models\Task;
use App\Enums\TaskStatus;

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
        return [
            'tasks' => Task::where('assignee_id', auth()->id())
                ->whereNotIn('status', [TaskStatus::Done])
                ->orderBy('updated_at', 'desc')
                ->limit(5)
                ->get(),
        ];
    }
}; ?>

<div class="bg-white dark:bg-zinc-800 rounded-xl p-6 shadow-sm border border-zinc-200 dark:border-zinc-700">
    <flux:heading size="sm" class="mb-4 flex items-center gap-2">
        <flux:icon name="check-circle" class="size-5 text-success" />
        {{ __('dashboard.assigned_tasks') }}
    </flux:heading>

    @if($tasks->isEmpty())
        <flux:text class="text-zinc-500">
            {{ __('dashboard.no_tasks') }}
        </flux:text>
    @else
        <div class="space-y-3">
            @foreach($tasks as $task)
                <div class="flex items-center justify-between p-2 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700/50">
                    <div class="text-sm font-medium truncate">{{ $task->title }}</div>
                    <flux:badge size="sm" :color="$task->status->badgeColor()">
                        {{ $task->status->label() }}
                    </flux:badge>
                </div>
            @endforeach
        </div>
    @endif
</div>