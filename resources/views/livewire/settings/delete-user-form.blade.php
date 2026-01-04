<?php

use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new class extends Component {
    public string $password = '';

    /**
     * Delete the currently authenticated user.
     */
    public function deleteUser(Logout $logout): void
    {
        $this->validate([
            'password' => ['required', 'string', 'current_password'],
        ]);

        tap(Auth::user(), $logout(...))->delete();

        $this->redirect('/', navigate: true);
    }
}; ?>

<section class="mt-8 space-y-6">
    <div class="relative mb-5">
        <flux:heading>{{ __('settings.delete_account.title') }}</flux:heading>
        <flux:subheading>{{ __('settings.delete_account.description') }}</flux:subheading>
    </div>

    <flux:modal.trigger name="confirm-user-deletion">
        <flux:button variant="danger" x-data="" x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
            data-test="delete-user-button">
            {{ __('settings.delete_account.button') }}
        </flux:button>
    </flux:modal.trigger>

    <flux:modal name="confirm-user-deletion" :show="$errors->isNotEmpty()" focusable class="max-w-lg">
        <form method="POST" wire:submit="deleteUser" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('settings.delete_account.confirm_title') }}</flux:heading>

                <flux:subheading>
                    {{ __('settings.delete_account.confirm_description') }}
                </flux:subheading>
            </div>

            <flux:input wire:model="password" :label="__('auth.login.password')" type="password" />

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('settings.delete_account.cancel') }}</flux:button>
                </flux:modal.close>

                <flux:button variant="danger" type="submit" data-test="confirm-delete-user-button">
                    {{ __('settings.delete_account.button') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>
</section>