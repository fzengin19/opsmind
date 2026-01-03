# Faz 7: Contact Management (CRM)

**Süre:** 4 gün  
**Önkoşul:** Faz 6 (Google Sync - opsiyonel)  
**Çıktı:** Kişi rehberi, iletişim geçmişi, randevu bağlantısı

---

## Amaç

Müşteri, tedarikçi, partner ve adayları yönetmek için Class-based Volt, Spatie Data DTO ve Action classes ile CRM modülü.

---

## Contact Types (Enum)

| Tip | Label | Renk | Hex |
|-----|-------|------|-----|
| `customer` | Müşteri | Yeşil | `#10b981` |
| `vendor` | Tedarikçi | Mavi | `#3b82f6` |
| `partner` | İş Ortağı | Mor | `#8b5cf6` |
| `lead` | Aday | Turuncu | `#f59e0b` |

---

## Görevler

### 7.1 ContactData DTO

- [ ] `app/Data/ContactData.php`:
  ```php
  use Spatie\LaravelData\Data;
  use Spatie\LaravelData\Concerns\WireableData;
  use Spatie\LaravelData\Attributes\Validation\Required;
  use Spatie\LaravelData\Attributes\Validation\Max;
  use Spatie\LaravelData\Attributes\Validation\Email;
  
  class ContactData extends Data implements Wireable
  {
      use WireableData;
      
      public function __construct(
          #[Required]
          public ContactType $type,
          
          #[Required, Max(50)]
          public string $first_name,
          
          #[Required, Max(50)]
          public string $last_name,
          
          #[Email]
          public ?string $email = null,
          
          #[Max(20)]
          public ?string $phone = null,
          
          #[Max(100)]
          public ?string $company_name = null,
          
          #[Max(100)]
          public ?string $job_title = null,
          
          public ?string $notes = null,
          
          /** @var array<string> */
          public array $tags = [],
      ) {}
  }
  ```

### 7.2 Action Classes

- [ ] `app/Actions/Contacts/CreateContactAction.php`:
  ```php
  class CreateContactAction
  {
      public function execute(ContactData $data, User $user): Contact
      {
          return Contact::create([
              'company_id' => $user->company_id,
              'type' => $data->type,
              'first_name' => $data->first_name,
              'last_name' => $data->last_name,
              'email' => $data->email,
              'phone' => $data->phone,
              'company_name' => $data->company_name,
              'job_title' => $data->job_title,
              'notes' => $data->notes,
              'tags' => $data->tags,
              'created_by' => $user->id,
          ]);
      }
  }
  ```

- [ ] `app/Actions/Contacts/UpdateContactAction.php`
- [ ] `app/Actions/Contacts/DeleteContactAction.php`
- [ ] `app/Actions/Contacts/AddContactActivityAction.php`

### 7.3 Contact List Page (Class-based Volt)

- [ ] `resources/views/livewire/contacts/index.blade.php`:
  ```php
  <?php
  use Livewire\Volt\Component;
  use Livewire\Attributes\Layout;
  use Livewire\Attributes\Url;
  use Livewire\WithPagination;
  use App\Enums\ContactType;
  
  new #[Layout('components.layouts.app')] class extends Component {
      use WithPagination;
      
      #[Url]
      public string $search = '';
      
      #[Url]
      public array $types = [];
      
      #[Url]
      public array $tags = [];
      
      #[Url]
      public string $sortBy = 'first_name';
      
      #[Url]
      public string $sortDir = 'asc';
      
      public function updatedSearch(): void
      {
          $this->resetPage();
      }
      
      public function sort(string $column): void
      {
          if ($this->sortBy === $column) {
              $this->sortDir = $this->sortDir === 'asc' ? 'desc' : 'asc';
          } else {
              $this->sortBy = $column;
              $this->sortDir = 'asc';
          }
      }
      
      public function clearFilters(): void
      {
          $this->reset(['search', 'types', 'tags']);
          $this->resetPage();
      }
      
      #[Computed]
      public function contacts(): LengthAwarePaginator
      {
          return Contact::query()
              ->where('company_id', auth()->user()->company_id)
              ->when($this->search, fn ($q) => $q
                  ->where('first_name', 'ilike', "%{$this->search}%")
                  ->orWhere('last_name', 'ilike', "%{$this->search}%")
                  ->orWhere('email', 'ilike', "%{$this->search}%")
                  ->orWhere('company_name', 'ilike', "%{$this->search}%")
              )
              ->when($this->types, fn ($q) => $q->whereIn('type', $this->types))
              ->when($this->tags, fn ($q) => $q->whereJsonContains('tags', $this->tags))
              ->orderBy($this->sortBy, $this->sortDir)
              ->paginate(15);
      }
      
      #[Computed]
      public function allTags(): array
      {
          return Contact::where('company_id', auth()->user()->company_id)
              ->pluck('tags')
              ->flatten()
              ->unique()
              ->values()
              ->toArray();
      }
  }; ?>
  
  <div>
      <div class="flex justify-between items-center mb-6">
          <flux:heading size="xl">Kişiler</flux:heading>
          <flux:button variant="primary" icon="plus" x-on:click="$flux.open('contact-form')">
              Yeni Kişi
          </flux:button>
      </div>
      
      <!-- Filters -->
      <div class="flex gap-4 mb-6">
          <flux:input 
              wire:model.live.debounce.300ms="search" 
              placeholder="İsim, email veya şirket ara..." 
              icon="magnifying-glass"
              class="flex-1"
          />
          
          <flux:select wire:model.live="types" multiple placeholder="Tür">
              @foreach(ContactType::cases() as $type)
                  <option value="{{ $type->value }}">{{ $type->label() }}</option>
              @endforeach
          </flux:select>
          
          @if($search || $types || $tags)
              <flux:button variant="ghost" wire:click="clearFilters">
                  Temizle
              </flux:button>
          @endif
      </div>
      
      <!-- Table -->
      <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm overflow-hidden">
          <table class="w-full">
              <thead class="bg-zinc-50 dark:bg-zinc-900">
                  <tr>
                      <th wire:click="sort('first_name')" 
                          class="px-4 py-3 text-left cursor-pointer hover:bg-zinc-100 dark:hover:bg-zinc-800">
                          İsim
                          @if($sortBy === 'first_name')
                              <flux:icon name="{{ $sortDir === 'asc' ? 'chevron-up' : 'chevron-down' }}" class="w-4 h-4 inline" />
                          @endif
                      </th>
                      <th class="px-4 py-3 text-left">Şirket</th>
                      <th class="px-4 py-3 text-left">Tür</th>
                      <th class="px-4 py-3 text-left">Email</th>
                      <th class="px-4 py-3 text-left">Telefon</th>
                      <th class="px-4 py-3"></th>
                  </tr>
              </thead>
              <tbody class="divide-y dark:divide-zinc-700">
                  @forelse($this->contacts as $contact)
                  <tr wire:key="contact-{{ $contact->id }}" 
                      class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50">
                      <td class="px-4 py-3">
                          <a href="{{ route('contacts.show', $contact) }}" 
                             class="font-medium hover:text-brand-600">
                              {{ $contact->full_name }}
                          </a>
                      </td>
                      <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">
                          {{ $contact->company_name ?? '-' }}
                      </td>
                      <td class="px-4 py-3">
                          <flux:badge :color="$contact->type->color()">
                              {{ $contact->type->label() }}
                          </flux:badge>
                      </td>
                      <td class="px-4 py-3">{{ $contact->email ?? '-' }}</td>
                      <td class="px-4 py-3">{{ $contact->phone ?? '-' }}</td>
                      <td class="px-4 py-3">
                          <flux:dropdown>
                              <flux:button variant="ghost" icon="ellipsis-horizontal" />
                              <flux:menu>
                                  <flux:menu.item href="{{ route('contacts.show', $contact) }}" icon="eye">
                                      Görüntüle
                                  </flux:menu.item>
                                  <flux:menu.item wire:click="$dispatch('edit-contact', { id: {{ $contact->id }} })" icon="pencil">
                                      Düzenle
                                  </flux:menu.item>
                              </flux:menu>
                          </flux:dropdown>
                      </td>
                  </tr>
                  @empty
                  <tr>
                      <td colspan="6" class="px-4 py-12 text-center text-zinc-500">
                          Henüz kişi eklenmemiş.
                      </td>
                  </tr>
                  @endforelse
              </tbody>
          </table>
          
          <div class="px-4 py-3 border-t dark:border-zinc-700">
              {{ $this->contacts->links() }}
          </div>
      </div>
      
      <!-- Contact Form Modal -->
      <flux:modal name="contact-form" class="max-w-xl">
          <livewire:contacts.form />
      </flux:modal>
  </div>
  ```

### 7.4 Contact Form Component

- [ ] `resources/views/livewire/contacts/form.blade.php`:
  ```php
  <?php
  use Livewire\Volt\Component;
  use Livewire\Attributes\On;
  use App\Data\ContactData;
  use App\Actions\Contacts\CreateContactAction;
  use App\Actions\Contacts\UpdateContactAction;
  
  new class extends Component {
      public ?Contact $contact = null;
      
      // Form fields
      public string $type = 'customer';
      public string $first_name = '';
      public string $last_name = '';
      public ?string $email = null;
      public ?string $phone = null;
      public ?string $company_name = null;
      public ?string $job_title = null;
      public ?string $notes = null;
      public array $tags = [];
      public string $newTag = '';
      
      #[On('edit-contact')]
      public function loadContact(int $id): void
      {
          $this->contact = Contact::findOrFail($id);
          $this->fill($this->contact->toArray());
      }
      
      public function addTag(): void
      {
          if ($this->newTag && !in_array($this->newTag, $this->tags)) {
              $this->tags[] = $this->newTag;
              $this->newTag = '';
          }
      }
      
      public function removeTag(int $index): void
      {
          unset($this->tags[$index]);
          $this->tags = array_values($this->tags);
      }
      
      public function save(
          CreateContactAction $createAction,
          UpdateContactAction $updateAction
      ): void {
          $data = ContactData::validateAndCreate([
              'type' => ContactType::from($this->type),
              'first_name' => $this->first_name,
              'last_name' => $this->last_name,
              'email' => $this->email,
              'phone' => $this->phone,
              'company_name' => $this->company_name,
              'job_title' => $this->job_title,
              'notes' => $this->notes,
              'tags' => $this->tags,
          ]);
          
          if ($this->contact) {
              $updateAction->execute($this->contact, $data);
          } else {
              $createAction->execute($data, auth()->user());
          }
          
          $this->dispatch('contact-saved');
          $this->dispatch('close-modal');
          $this->reset();
      }
  }; ?>
  
  <div class="p-6">
      <flux:heading size="lg" class="mb-6">
          {{ $contact ? 'Kişiyi Düzenle' : 'Yeni Kişi' }}
      </flux:heading>
      
      <form wire:submit="save" class="space-y-4">
          <flux:select wire:model="type" label="Tür" required>
              <option value="customer">Müşteri</option>
              <option value="vendor">Tedarikçi</option>
              <option value="partner">İş Ortağı</option>
              <option value="lead">Aday</option>
          </flux:select>
          
          <div class="grid grid-cols-2 gap-4">
              <flux:input wire:model.blur="first_name" label="Ad" required />
              <flux:input wire:model.blur="last_name" label="Soyad" required />
          </div>
          
          <div class="grid grid-cols-2 gap-4">
              <flux:input wire:model.blur="email" type="email" label="Email" />
              <flux:input wire:model.blur="phone" label="Telefon" />
          </div>
          
          <div class="grid grid-cols-2 gap-4">
              <flux:input wire:model.blur="company_name" label="Şirket" />
              <flux:input wire:model.blur="job_title" label="Pozisyon" />
          </div>
          
          <!-- Tags -->
          <div>
              <flux:label>Etiketler</flux:label>
              <div class="flex gap-2 mt-1">
                  <flux:input 
                      wire:model="newTag" 
                      wire:keydown.enter.prevent="addTag"
                      placeholder="Etiket ekle..." 
                      class="flex-1"
                  />
                  <flux:button type="button" wire:click="addTag" variant="outline">
                      Ekle
                  </flux:button>
              </div>
              <div class="flex flex-wrap gap-2 mt-2">
                  @foreach($tags as $index => $tag)
                      <flux:badge dismissable wire:click="removeTag({{ $index }})">
                          {{ $tag }}
                      </flux:badge>
                  @endforeach
              </div>
          </div>
          
          <flux:textarea wire:model.blur="notes" label="Notlar" rows="3" />
          
          <div class="flex justify-end gap-2 pt-4">
              <flux:button variant="ghost" x-on:click="$flux.close()">İptal</flux:button>
              <flux:button type="submit" variant="primary">
                  {{ $contact ? 'Güncelle' : 'Oluştur' }}
              </flux:button>
          </div>
      </form>
  </div>
  ```

### 7.5 Contact Profile Page

- [ ] `resources/views/livewire/contacts/show.blade.php`:
  ```php
  <?php
  use Livewire\Volt\Component;
  use Livewire\Attributes\Layout;
  
  new #[Layout('components.layouts.app')] class extends Component {
      public Contact $contact;
      
      public function mount(Contact $contact): void
      {
          $this->authorize('view', $contact);
          $this->contact = $contact->load(['activities.user', 'appointments']);
      }
  }; ?>
  
  <div class="max-w-4xl">
      <!-- Header -->
      <div class="flex items-center justify-between mb-6">
          <div class="flex items-center gap-4">
              <flux:avatar size="xl" :name="$contact->full_name" />
              <div>
                  <flux:heading size="xl">{{ $contact->full_name }}</flux:heading>
                  <flux:text class="text-zinc-500">
                      {{ $contact->job_title }} @ {{ $contact->company_name }}
                  </flux:text>
              </div>
          </div>
          <flux:badge :color="$contact->type->color()">
              {{ $contact->type->label() }}
          </flux:badge>
      </div>
      
      <div class="grid grid-cols-3 gap-6">
          <!-- Info Card -->
          <div class="col-span-1 bg-white dark:bg-zinc-800 rounded-lg shadow-sm p-6">
              <flux:heading size="sm" class="mb-4">İletişim Bilgileri</flux:heading>
              
              <div class="space-y-3">
                  @if($contact->email)
                  <div class="flex items-center gap-2">
                      <flux:icon name="envelope" class="w-5 h-5 text-zinc-400" />
                      <a href="mailto:{{ $contact->email }}" class="hover:text-brand-600">
                          {{ $contact->email }}
                      </a>
                  </div>
                  @endif
                  
                  @if($contact->phone)
                  <div class="flex items-center gap-2">
                      <flux:icon name="phone" class="w-5 h-5 text-zinc-400" />
                      <a href="tel:{{ $contact->phone }}" class="hover:text-brand-600">
                          {{ $contact->phone }}
                      </a>
                  </div>
                  @endif
              </div>
              
              @if($contact->tags)
              <div class="mt-4 pt-4 border-t dark:border-zinc-700">
                  <flux:label>Etiketler</flux:label>
                  <div class="flex flex-wrap gap-1 mt-2">
                      @foreach($contact->tags as $tag)
                          <flux:badge size="sm">{{ $tag }}</flux:badge>
                      @endforeach
                  </div>
              </div>
              @endif
          </div>
          
          <!-- Activity Timeline -->
          <div class="col-span-2">
              <livewire:contacts.activity-timeline :contact="$contact" />
          </div>
      </div>
      
      <!-- Related Appointments -->
      <div class="mt-6 bg-white dark:bg-zinc-800 rounded-lg shadow-sm p-6">
          <flux:heading size="sm" class="mb-4">Katıldığı Randevular</flux:heading>
          
          @forelse($contact->appointments as $appointment)
              <div class="flex items-center justify-between py-2 border-b dark:border-zinc-700 last:border-0">
                  <div>
                      <span class="font-medium">{{ $appointment->title }}</span>
                      <span class="text-zinc-500 text-sm ml-2">
                          {{ $appointment->start_at->format('d M Y, H:i') }}
                      </span>
                  </div>
                  <a href="{{ route('calendar') }}" class="text-brand-600 hover:underline text-sm">
                      Görüntüle
                  </a>
              </div>
          @empty
              <flux:text class="text-zinc-500">Henüz randevu yok.</flux:text>
          @endforelse
      </div>
  </div>
  ```

### 7.6 Activity Timeline Component

- [ ] `contact_activities` migration:
  ```php
  Schema::create('contact_activities', function (Blueprint $table) {
      $table->id();
      $table->foreignId('contact_id')->constrained()->cascadeOnDelete();
      $table->foreignId('user_id')->constrained()->cascadeOnDelete();
      $table->string('type'); // note, call, email, meeting
      $table->text('note');
      $table->timestamp('created_at');
  });
  ```

- [ ] `resources/views/livewire/contacts/activity-timeline.blade.php`:
  ```php
  <?php
  use Livewire\Volt\Component;
  use App\Actions\Contacts\AddContactActivityAction;
  
  new class extends Component {
      public Contact $contact;
      public string $activityType = 'note';
      public string $note = '';
      
      public function addActivity(AddContactActivityAction $action): void
      {
          $this->validate(['note' => 'required|min:3']);
          
          $action->execute($this->contact, auth()->user(), $this->activityType, $this->note);
          
          $this->reset(['note']);
          $this->contact->refresh();
      }
  }; ?>
  
  <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm p-6">
      <flux:heading size="sm" class="mb-4">İletişim Geçmişi</flux:heading>
      
      <!-- Add Activity Form -->
      <div class="mb-6 p-4 bg-zinc-50 dark:bg-zinc-900 rounded-lg">
          <div class="flex gap-2 mb-2">
              @foreach(['note' => 'Not', 'call' => 'Arama', 'email' => 'Email', 'meeting' => 'Toplantı'] as $type => $label)
                  <flux:button 
                      variant="{{ $activityType === $type ? 'primary' : 'ghost' }}"
                      wire:click="$set('activityType', '{{ $type }}')"
                      size="sm"
                  >
                      {{ $label }}
                  </flux:button>
              @endforeach
          </div>
          <flux:textarea wire:model="note" rows="2" placeholder="Not ekle..." />
          <div class="mt-2 flex justify-end">
              <flux:button wire:click="addActivity" variant="primary" size="sm">
                  Ekle
              </flux:button>
          </div>
      </div>
      
      <!-- Timeline -->
      <div class="space-y-4">
          @forelse($contact->activities()->latest()->get() as $activity)
          <div class="flex gap-3">
              <div class="flex-shrink-0">
                  <flux:avatar size="sm" :src="$activity->user->avatar" />
              </div>
              <div class="flex-1">
                  <div class="flex items-center gap-2">
                      <span class="font-medium">{{ $activity->user->name }}</span>
                      <flux:badge size="sm">{{ ucfirst($activity->type) }}</flux:badge>
                      <span class="text-zinc-500 text-sm">
                          {{ $activity->created_at->diffForHumans() }}
                      </span>
                  </div>
                  <p class="mt-1 text-zinc-600 dark:text-zinc-300">
                      {{ $activity->note }}
                  </p>
              </div>
          </div>
          @empty
          <flux:text class="text-zinc-500">Henüz aktivite yok.</flux:text>
          @endforelse
      </div>
  </div>
  ```

### 7.7 ContactPolicy

- [ ] `app/Policies/ContactPolicy.php`:
  ```php
  class ContactPolicy
  {
      public function viewAny(User $user): bool
      {
          return true;
      }
      
      public function view(User $user, Contact $contact): bool
      {
          return $contact->company_id === $user->company_id;
      }
      
      public function create(User $user): bool
      {
          return true;
      }
      
      public function update(User $user, Contact $contact): bool
      {
          if ($user->hasAnyRole(['admin', 'manager'])) {
              return $contact->company_id === $user->company_id;
          }
          
          return $contact->created_by === $user->id;
      }
      
      public function delete(User $user, Contact $contact): bool
      {
          return $user->hasAnyRole(['admin', 'manager']) 
              && $contact->company_id === $user->company_id;
      }
  }
  ```

### 7.8 Routes

- [ ] `routes/web.php`:
  ```php
  Route::middleware(['auth', 'verified'])->group(function () {
      Route::get('/contacts', fn() => view('livewire.contacts.index'))->name('contacts');
      Route::get('/contacts/{contact}', fn(Contact $contact) => view('livewire.contacts.show', compact('contact')))->name('contacts.show');
  });
  ```

---

## Doğrulama

```bash
php artisan test --filter=Contact
```

Manuel test:
1. Kişiler sayfasına git → Liste görünsün
2. "Yeni Kişi" → Form modal açılsın
3. Kişi oluştur → Listede görünsün
4. Arama yap → Filtrelesin
5. Tür filtresi kullan
6. Kişiye tıkla → Profil sayfası
7. Not ekle → Timeline'da görünsün
8. Takvimde randevu oluştur, bu kişiyi katılımcı ekle
9. Kişi profilinde randevu listesi görünsün

---

## Dosya Listesi

```
app/
├── Actions/Contacts/
│   ├── CreateContactAction.php
│   ├── UpdateContactAction.php
│   ├── DeleteContactAction.php
│   └── AddContactActivityAction.php
├── Data/
│   └── ContactData.php
├── Models/
│   ├── Contact.php (mevcut - güncelle)
│   └── ContactActivity.php
├── Policies/
│   └── ContactPolicy.php
└── Enums/
    └── ContactType.php (mevcut - label(), color() metodları ekle)

resources/views/livewire/contacts/
├── index.blade.php
├── show.blade.php
├── form.blade.php
└── activity-timeline.blade.php

database/migrations/
└── xxxx_create_contact_activities_table.php
```

---

## Model Güncellemeleri

```php
// app/Models/Contact.php
class Contact extends Model
{
    protected $casts = [
        'type' => ContactType::class,
        'tags' => 'array',
    ];
    
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }
    
    public function activities(): HasMany
    {
        return $this->hasMany(ContactActivity::class);
    }
    
    public function appointments(): BelongsToMany
    {
        return $this->belongsToMany(Appointment::class, 'appointment_attendees')
            ->withPivot('status')
            ->withTimestamps();
    }
}
```

---

## Notlar

- **wire:model.blur** form input'larında (performans)
- **wire:model.live.debounce** sadece arama input'unda
- **PostgreSQL ilike** case-insensitive arama için
- **JSONB tags** - flexible etiket sistemi
- Şirket entity'si (Company) Post-MVP
- Duplicate detection Post-MVP
- CSV import/export Post-MVP
