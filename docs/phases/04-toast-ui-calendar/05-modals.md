# Step 5: Modals & Interactivity

We bypass TOAST UI's native popups to maintain a unified design system using Flux UI Modals.

## 🏗 Modular Modal Architecture

Instead of cluttering the main `Calendar.php` with modal logic, we can:
1.  Keep simple state (`$showCreateModal`) in the main component (simpler).
2.  Or use a global Modal Manager (more complex).

For this Phase, keeping state in `Calendar.php` is acceptable and reduces complexity.

### 1. The `Create` Modal

**State:**
```php
public bool $showCreateModal = false;
public string $newTitle = '';
public string $types = AppointmentType::Meeting->value;
public $newStart;
public $newEnd;
```

**View:**
```blade
<flux:modal wire:model="showCreateModal" class="min-w-[400px]">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">Yeni Etkinlik</flux:heading>
            <flux:subheading>Takviminize yeni bir etkinlik ekleyin.</flux:subheading>
        </div>

        <flux:input label="Başlık" wire:model="newTitle" placeholder="Örn: Pazarlama Toplantısı" />

        <div class="grid grid-cols-2 gap-4">
            <flux:input type="datetime-local" label="Başlangıç" wire:model="newStart" />
            <flux:input type="datetime-local" label="Bitiş" wire:model="newEnd" />
        </div>

        <flux:select label="Tip" wire:model="newType">
            @foreach(AppointmentType::cases() as $type)
                <flux:option value="{{ $type->value }}">{{ $type->label() }}</flux:option>
            @endforeach
        </flux:select>
        
        <flux:textarea label="Açıklama" wire:model="newDescription" rows="3" />

        <div class="flex justify-end gap-2">
            <flux:button variant="ghost" @click="$wire.showCreateModal = false">İptal</flux:button>
            <flux:button variant="primary" wire:click="createAppointment">Oluştur</flux:button>
        </div>
    </div>
</flux:modal>
```

### 2. The `Detail` Modal

This modal displays read-only info and offers "Edit" or "Delete" actions.

**View:**
```blade
<flux:modal wire:model="showDetailModal">
    @if($selectedAppointment)
        <div class="space-y-4">
            <div class="flex justify-between items-start">
                <flux:heading size="xl">{{ $selectedAppointment->title }}</flux:heading>
                <flux:badge color="zinc">{{ $selectedAppointment->type->label() }}</flux:badge>
            </div>
            
            <div class="text-sm text-zinc-500 flex gap-4">
                <div class="flex items-center gap-1">
                    <flux:icon name="clock" class="size-4" />
                    <span>{{ $selectedAppointment->start_at->format('d M H:i') }} - {{ $selectedAppointment->end_at->format('H:i') }}</span>
                </div>
            </div>

            <div class="prose prose-sm dark:prose-invert">
                {{ $selectedAppointment->description }}
            </div>

            <div class="flex justify-between mt-6 pt-4 border-t border-zinc-200 dark:border-zinc-700">
                <flux:button variant="danger" wire:click="deleteAppointment({{ $selectedAppointment->id }})">Sil</flux:button>
                <flux:button variant="primary" wire:click="editAppointment({{ $selectedAppointment->id }})">Düzenle</flux:button>
            </div>
        </div>
    @endif
</flux:modal>
```

## 🔗 Interaction Flow

1.  **User Clicks Empty Grid:** `selectDateTime` event -> JS calls `$dispatch('open-create-modal', { start, end })` -> Livewire sets state -> Modal opens.
2.  **User Clicks Event:** `clickEvent` event -> JS calls `$dispatch('open-detail-modal', { id })` -> Livewire fetches record -> Modal opens.

## ✅ Checklist
- [ ] Create `createAppointment` action with validation.
- [ ] Create `deleteAppointment` action with authorization.
- [ ] Connect Alpine events to Livewire modal states.
