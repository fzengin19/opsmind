<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;

new #[Layout('components.layouts.app')] class extends Component {
    public function with(): array
    {
        return [
            'user' => auth()->user(),
            'company' => auth()->user()->currentCompany(),
        ];
    }
}; ?>

<div>
    <flux:heading size="xl" class="mb-6">
        {{ __('dashboard.welcome', ['name' => $user->name]) }} 👋
    </flux:heading>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <livewire:dashboard.today-appointments />
        <livewire:dashboard.assigned-tasks />
        <livewire:dashboard.recent-activity />
    </div>

    <!-- Quick Actions -->
    <div class="mt-8 flex flex-wrap gap-4">
        <flux:button variant="primary" icon="plus" disabled>
            {{ __('dashboard.new_appointment') }}
        </flux:button>
        <flux:button variant="outline" icon="plus" disabled>
            {{ __('dashboard.new_task') }}
        </flux:button>
        <flux:button variant="outline" icon="user-plus" disabled>
            {{ __('dashboard.new_contact') }}
        </flux:button>
    </div>
</div>