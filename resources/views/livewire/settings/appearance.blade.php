<?php

use Livewire\Volt\Component;

new class extends Component {
    //
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('settings.appearance.title')" :subheading="__('settings.appearance.description')">
        <flux:radio.group x-data variant="segmented" x-model="$flux.appearance">
            <flux:radio value="light" icon="sun">{{ __('settings.appearance.light') }}</flux:radio>
            <flux:radio value="dark" icon="moon">{{ __('settings.appearance.dark') }}</flux:radio>
            <flux:radio value="system" icon="computer-desktop">{{ __('settings.appearance.system') }}</flux:radio>
        </flux:radio.group>
    </x-settings.layout>
</section>
