<?php

use App\Actions\Auth\CreateCompanyAction;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new #[\Livewire\Attributes\Layout('components.layouts.auth.simple')] class extends Component {
    public string $companyName = '';

    public function rules(): array
    {
        return [
            'companyName' => ['required', 'string', 'min:2', 'max:100'],
        ];
    }

    public function createCompany(CreateCompanyAction $action): void
    {
        $this->validate();

        $action->execute(Auth::user(), $this->companyName);

        $this->redirect(route('dashboard'), navigate: true);
    }
}; ?>

<div class="flex flex-col gap-6">
    <x-auth-header :title="__('Firma Oluştur')" :description="__('Hesabınız için bir firma oluşturun')" />

    <form wire:submit="createCompany" class="flex flex-col gap-6">
        <flux:input wire:model="companyName" :label="__('Firma Adı')" type="text" required autofocus
            placeholder="Örn: ABC Teknoloji Ltd." />

        <div class="flex items-center justify-end">
            <flux:button variant="primary" type="submit" class="w-full">
                {{ __('Firma Oluştur') }}
            </flux:button>
        </div>
    </form>
</div>