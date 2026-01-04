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
    <x-auth-header :title="__('onboarding.title')" :description="__('onboarding.description')" />

    <form wire:submit="createCompany" class="flex flex-col gap-6">
        <flux:input wire:model="companyName" :label="__('onboarding.company_name')" type="text" required autofocus
            :placeholder="__('onboarding.company_name_placeholder')" />

        <div class="flex items-center justify-end">
            <flux:button variant="primary" type="submit" class="w-full">
                {{ __('onboarding.create_button') }}
            </flux:button>
        </div>
    </form>
</div>