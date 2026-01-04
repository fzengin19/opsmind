<x-layouts.auth>
    <div class="flex flex-col gap-6">
        <x-auth-header
            :title="__('auth.password.confirm_title')"
            :description="__('auth.password.confirm_description')"
        />

        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('password.confirm.store') }}" class="flex flex-col gap-6">
            @csrf

            <flux:input
                name="password"
                :label="__('auth.login.password')"
                type="password"
                required
                autocomplete="current-password"
                :placeholder="__('auth.login.password')"
                viewable
            />

            <flux:button variant="primary" type="submit" class="w-full" data-test="confirm-password-button">
                {{ __('auth.password.confirm_button') }}
            </flux:button>
        </form>
    </div>
</x-layouts.auth>
