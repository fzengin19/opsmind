<?php

use Livewire\Volt\Component;
use Livewire\Attributes\On;
use App\Actions\Appointments\DeleteAppointmentAction;
use App\Models\Appointment;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

new class extends Component {
    use AuthorizesRequests;

    public bool $showModal = false;
    public ?Appointment $appointment = null;

    #[On('confirm-delete-appointment')]
    public function open(int $appointmentId): void
    {
        $this->appointment = Appointment::findOrFail($appointmentId);
        $this->authorize('delete', $this->appointment);
        $this->showModal = true;
    }

    public function delete(DeleteAppointmentAction $action): void
    {
        $action->execute($this->appointment);
        $this->showModal = false;
        $this->dispatch('calendar-refresh');
    }
}; ?>

<flux:modal wire:model="showModal" class="max-w-md"
    x-on:keydown.escape.window="if ($wire.showModal) $wire.set('showModal', false)">
    <div class="p-6">
        <flux:heading size="lg">{{ __('calendar.delete_appointment') }}</flux:heading>

        <flux:text class="mt-4">
            {{ __('calendar.delete_confirmation', ['title' => $appointment?->title]) }}
        </flux:text>

        <div class="flex justify-end gap-2 mt-6">
            <flux:button variant="ghost" wire:click="$set('showModal', false)">
                {{ __('common.cancel') }}
            </flux:button>
            <flux:button variant="danger" wire:click="delete">
                {{ __('common.delete') }}
            </flux:button>
        </div>
    </div>
</flux:modal>