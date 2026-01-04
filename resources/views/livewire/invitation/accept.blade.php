<?php

use App\Actions\Auth\AcceptInvitationAction;
use App\Models\Invitation;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new #[\Livewire\Attributes\Layout('components.layouts.auth.simple')] class extends Component {
    public ?Invitation $invitation = null;
    public string $token = '';
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';
    public bool $isExpired = false;
    public bool $isAccepted = false;
    public bool $userExists = false;

    public function mount(string $token): void
    {
        $this->token = $token;
        $this->invitation = Invitation::where('token', $token)->first();

        if (!$this->invitation) {
            abort(404, 'Davet bulunamadı.');
        }

        $this->isExpired = $this->invitation->isExpired();
        $this->isAccepted = $this->invitation->isAccepted();
        $this->email = $this->invitation->email;

        // Check if user already exists
        $this->userExists = \App\Models\User::where('email', $this->email)->exists();

        // If logged in user's email matches, auto-accept
        if (Auth::check() && Auth::user()->email === $this->email && $this->invitation->isValid()) {
            $this->acceptAsLoggedInUser();
        }
    }

    public function rules(): array
    {
        if ($this->userExists) {
            return [
                'password' => ['required', 'string'],
            ];
        }

        return [
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function acceptInvitation(AcceptInvitationAction $action): void
    {
        if (!$this->invitation || !$this->invitation->isValid()) {
            return;
        }

        $this->validate();

        if ($this->userExists) {
            // Login existing user
            if (!Auth::attempt(['email' => $this->email, 'password' => $this->password])) {
                $this->addError('password', 'Şifre hatalı.');

                return;
            }

            $user = Auth::user();
        } else {
            // Create new user
            $user = \App\Models\User::create([
                'name' => $this->name,
                'email' => $this->email,
                'password' => $this->password,
                'email_verified_at' => now(),
            ]);

            Auth::login($user);
        }

        $action->execute($this->invitation, $user);

        $this->redirect(route('dashboard'), navigate: true);
    }

    public function acceptAsLoggedInUser(): void
    {
        if (!$this->invitation || !$this->invitation->isValid()) {
            return;
        }

        $action = app(AcceptInvitationAction::class);
        $action->execute($this->invitation, Auth::user());

        $this->redirect(route('dashboard'), navigate: true);
    }
}; ?>

<div class="flex flex-col gap-6">
    <x-auth-header :title="__('Takım Daveti')" :description="$invitation ? $invitation->company->name . ' takımına davet edildiniz' : ''" />

    @if ($isExpired)
        <flux:callout variant="danger">
            <flux:callout.heading>Davet Süresi Doldu</flux:callout.heading>
            <flux:callout.text>Bu davet artık geçerli değil. Lütfen yöneticinizden yeni bir davet isteyin.
            </flux:callout.text>
        </flux:callout>
    @elseif ($isAccepted)
        <flux:callout variant="warning">
            <flux:callout.heading>Davet Zaten Kullanıldı</flux:callout.heading>
            <flux:callout.text>Bu davet daha önce kabul edilmiş.</flux:callout.text>
        </flux:callout>
        <a href="{{ route('login') }}" class="text-center">
            <flux:button variant="primary" class="w-full">Giriş Yap</flux:button>
        </a>
    @elseif (auth()->check() && auth()->user()->email === $email)
        <p class="text-center text-zinc-600 dark:text-zinc-400">Daveti otomatik kabul ediyoruz...</p>
    @else
        <form wire:submit="acceptInvitation" class="flex flex-col gap-6">
            @if ($userExists)
                <flux:callout variant="info">
                    <flux:callout.text>Bu email adresi ile kayıtlı bir hesap var. Giriş yaparak daveti kabul
                        edebilirsiniz.
                    </flux:callout.text>
                </flux:callout>

                <flux:input :value="$email" :label="__('Email')" type="email" disabled />

                <flux:input wire:model="password" :label="__('Şifre')" type="password" required placeholder="Mevcut şifreniz"
                    viewable />
            @else
                <flux:input wire:model="name" :label="__('Ad Soyad')" type="text" required autofocus
                    placeholder="Adınız Soyadınız" />

                <flux:input :value="$email" :label="__('Email')" type="email" disabled />

                <flux:input wire:model="password" :label="__('Şifre')" type="password" required placeholder="En az 8 karakter"
                    viewable />

                <flux:input wire:model="password_confirmation" :label="__('Şifre Tekrar')" type="password" required
                    placeholder="Şifrenizi tekrar girin" viewable />
            @endif

            <flux:button variant="primary" type="submit" class="w-full">
                {{ $userExists ? __('Giriş Yap ve Daveti Kabul Et') : __('Kayıt Ol ve Daveti Kabul Et') }}
            </flux:button>
        </form>
    @endif
</div>