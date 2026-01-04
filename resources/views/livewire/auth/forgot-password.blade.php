<x-layouts.auth>
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('auth.password.forgot_title')"
            :description="__('auth.password.forgot_description')" />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('password.email') }}" class="flex flex-col gap-6">
            @csrf

            <!-- Email Address -->
            <flux:input name="email" :label="__('auth.login.email')" type="email" required autofocus
                placeholder="email@example.com" />

            <flux:button variant="primary" type="submit" class="w-full" data-test="email-password-reset-link-button">
                {{ __('auth.password.send_link') }}
            </flux:button>
        </form>

        <div class="space-x-1 rtl:space-x-reverse text-center text-sm text-zinc-400">
            <span>{{ __('auth.password.or_return') }}</span>
            <flux:link :href="route('login')" wire:navigate>{{ __('auth.login.button') }}</flux:link>
        </div>
    </div>
</x-layouts.auth>