# Faz 8: Kanban Task Board

**Süre:** 5 gün  
**Önkoşul:** Faz 7 (Contact Management)  
**Çıktı:** Sürükle-bırak görev panosu, yorumlar, checklist

---

## Amaç

Sortable.js, Class-based Volt ve Action classes ile Trello benzeri Kanban görev panosu.

---

## Board Yapısı

```
┌─────────────┬─────────────┬─────────────┬─────────────┬─────────────┐
│   BACKLOG   │    TODO     │ IN PROGRESS │   REVIEW    │    DONE     │
├─────────────┼─────────────┼─────────────┼─────────────┼─────────────┤
│ [🔴 Task 1] │ [🟡 Task 3] │ [🟢 Task 5] │ [🔵 Task 7] │ [⚪ Task 9] │
│ [🟡 Task 2] │ [🔴 Task 4] │             │             │             │
└─────────────┴─────────────┴─────────────┴─────────────┴─────────────┘
```

---

## TaskStatus Enum

| Status | Label | Renk |
|--------|-------|------|
| `backlog` | Beklemede | Gri `#6b7280` |
| `todo` | Yapılacak | Mavi `#3b82f6` |
| `in_progress` | Devam Ediyor | Sarı `#f59e0b` |
| `review` | İnceleme | Mor `#8b5cf6` |
| `done` | Tamamlandı | Yeşil `#10b981` |

---

## Görevler

### 8.1 TaskData DTO

- [ ] `app/Data/TaskData.php`:
  ```php
  class TaskData extends Data implements Wireable
  {
      use WireableData;
      
      public function __construct(
          #[Required, Max(200)]
          public string $title,
          
          public ?string $description = null,
          
          #[Required]
          public TaskStatus $status = TaskStatus::Backlog,
          
          #[Required]
          public TaskPriority $priority = TaskPriority::Medium,
          
          public ?Carbon $due_date = null,
          
          public ?float $estimated_hours = null,
          
          public ?int $assignee_id = null,
          
          public ?int $contact_id = null,
          
          public ?int $appointment_id = null,
          
          /** @var array<array{text: string, completed: bool}> */
          public array $checklist = [],
      ) {}
  }
  ```

### 8.2 Sortable.js Kurulumu

- [ ] NPM install:
  ```bash
  npm install sortablejs
  ```

- [ ] `resources/js/sortable.js`:
  ```javascript
  import Sortable from 'sortablejs';
  
  window.initSortable = function(el, options) {
      return new Sortable(el, {
          group: 'tasks',
          animation: 150,
          ghostClass: 'opacity-50',
          dragClass: 'shadow-lg',
          handle: '.drag-handle',
          onEnd: (evt) => {
              const taskId = evt.item.dataset.taskId;
              const newStatus = evt.to.dataset.status;
              const newIndex = evt.newIndex;
              
              // Livewire event dispatch
              if (options.onReorder) {
                  options.onReorder(taskId, newStatus, newIndex);
              }
          }
      });
  };
  ```

- [ ] `resources/js/app.js`:
  ```javascript
  import './sortable.js';
  ```

### 8.3 Action Classes

- [ ] `app/Actions/Tasks/CreateTaskAction.php`:
  ```php
  class CreateTaskAction
  {
      public function execute(TaskData $data, User $user): Task
      {
          $maxPosition = Task::where('company_id', $user->company_id)
              ->where('status', $data->status)
              ->max('position') ?? 0;
          
          return Task::create([
              'company_id' => $user->company_id,
              'title' => $data->title,
              'description' => $data->description,
              'status' => $data->status,
              'priority' => $data->priority,
              'due_date' => $data->due_date,
              'estimated_hours' => $data->estimated_hours,
              'assignee_id' => $data->assignee_id,
              'contact_id' => $data->contact_id,
              'appointment_id' => $data->appointment_id,
              'checklist' => $data->checklist,
              'position' => $maxPosition + 1,
              'created_by' => $user->id,
          ]);
      }
  }
  ```

- [ ] `app/Actions/Tasks/UpdateTaskAction.php`
- [ ] `app/Actions/Tasks/DeleteTaskAction.php`
- [ ] `app/Actions/Tasks/ReorderTaskAction.php`:
  ```php
  class ReorderTaskAction
  {
      public function execute(Task $task, TaskStatus $newStatus, int $newPosition): void
      {
          DB::transaction(function () use ($task, $newStatus, $newPosition) {
              $oldStatus = $task->status;
              $oldPosition = $task->position;
              
              if ($oldStatus === $newStatus) {
                  // Aynı sütun içinde sıralama
                  if ($oldPosition < $newPosition) {
                      Task::where('company_id', $task->company_id)
                          ->where('status', $newStatus)
                          ->whereBetween('position', [$oldPosition + 1, $newPosition])
                          ->decrement('position');
                  } else {
                      Task::where('company_id', $task->company_id)
                          ->where('status', $newStatus)
                          ->whereBetween('position', [$newPosition, $oldPosition - 1])
                          ->increment('position');
                  }
              } else {
                  // Farklı sütuna taşıma
                  Task::where('company_id', $task->company_id)
                      ->where('status', $oldStatus)
                      ->where('position', '>', $oldPosition)
                      ->decrement('position');
                  
                  Task::where('company_id', $task->company_id)
                      ->where('status', $newStatus)
                      ->where('position', '>=', $newPosition)
                      ->increment('position');
              }
              
              $task->update([
                  'status' => $newStatus,
                  'position' => $newPosition,
              ]);
          });
      }
  }
  ```

- [ ] `app/Actions/Tasks/AddTaskCommentAction.php`

### 8.4 Kanban Board Component (Class-based Volt)

- [ ] `resources/views/livewire/tasks/board.blade.php`:
  ```php
  <?php
  use Livewire\Volt\Component;
  use Livewire\Attributes\Layout;
  use Livewire\Attributes\On;
  use Livewire\Attributes\Url;
  use App\Enums\TaskStatus;
  use App\Enums\TaskPriority;
  use App\Actions\Tasks\ReorderTaskAction;
  
  new #[Layout('components.layouts.app')] class extends Component {
      #[Url]
      public ?int $assigneeFilter = null;
      
      #[Url]
      public ?string $priorityFilter = null;
      
      public bool $showMyTasks = false;
      
      #[On('task-reordered')]
      public function reorderTask(
          int $taskId, 
          string $newStatus, 
          int $newPosition,
          ReorderTaskAction $action
      ): void {
          $task = Task::findOrFail($taskId);
          $this->authorize('update', $task);
          
          $action->execute($task, TaskStatus::from($newStatus), $newPosition);
      }
      
      public function toggleMyTasks(): void
      {
          $this->showMyTasks = !$this->showMyTasks;
          $this->assigneeFilter = $this->showMyTasks ? auth()->id() : null;
      }
      
      #[Computed]
      public function tasksByStatus(): array
      {
          $query = Task::query()
              ->where('company_id', auth()->user()->company_id)
              ->when($this->assigneeFilter, fn ($q) => $q->where('assignee_id', $this->assigneeFilter))
              ->when($this->priorityFilter, fn ($q) => $q->where('priority', $this->priorityFilter))
              ->with(['assignee', 'comments'])
              ->orderBy('position');
          
          $tasks = $query->get();
          
          return collect(TaskStatus::cases())
              ->mapWithKeys(fn ($status) => [
                  $status->value => $tasks->where('status', $status)
              ])
              ->toArray();
      }
      
      #[Computed]
      public function teamMembers(): Collection
      {
          return User::where('company_id', auth()->user()->company_id)->get();
      }
  }; ?>
  
  <div>
      <!-- Header -->
      <div class="flex justify-between items-center mb-6">
          <flux:heading size="xl">Görevler</flux:heading>
          
          <div class="flex gap-2">
              <flux:button 
                  :variant="$showMyTasks ? 'primary' : 'outline'"
                  wire:click="toggleMyTasks"
              >
                  Bana Atananlar
              </flux:button>
              
              <flux:select wire:model.live="priorityFilter" placeholder="Öncelik">
                  <option value="">Tümü</option>
                  @foreach(TaskPriority::cases() as $priority)
                      <option value="{{ $priority->value }}">{{ $priority->label() }}</option>
                  @endforeach
              </flux:select>
              
              <flux:button variant="primary" icon="plus" x-on:click="$flux.open('task-form')">
                  Yeni Görev
              </flux:button>
          </div>
      </div>
      
      <!-- Kanban Board -->
      <div class="flex gap-4 overflow-x-auto pb-4" x-data="kanbanBoard()">
          @foreach(TaskStatus::cases() as $status)
          <div class="flex-shrink-0 w-80">
              <!-- Column Header -->
              <div class="flex items-center justify-between px-3 py-2 bg-zinc-100 dark:bg-zinc-900 rounded-t-lg">
                  <div class="flex items-center gap-2">
                      <div class="w-3 h-3 rounded-full" style="background-color: {{ $status->color() }}"></div>
                      <span class="font-medium">{{ $status->label() }}</span>
                      <flux:badge size="sm">
                          {{ count($this->tasksByStatus[$status->value] ?? []) }}
                      </flux:badge>
                  </div>
                  <flux:button 
                      variant="ghost" 
                      size="sm" 
                      icon="plus"
                      wire:click="$dispatch('quick-create', { status: '{{ $status->value }}' })"
                  />
              </div>
              
              <!-- Column Body -->
              <div 
                  class="bg-zinc-50 dark:bg-zinc-800/50 rounded-b-lg p-2 min-h-[500px] space-y-2"
                  data-status="{{ $status->value }}"
                  x-ref="column-{{ $status->value }}"
                  x-init="initColumn($refs['column-{{ $status->value }}'])"
              >
                  @foreach($this->tasksByStatus[$status->value] ?? [] as $task)
                      <livewire:tasks.card :task="$task" :key="'task-'.$task->id" />
                  @endforeach
              </div>
          </div>
          @endforeach
      </div>
      
      <!-- Task Form Modal -->
      <flux:modal name="task-form" class="max-w-xl">
          <livewire:tasks.form />
      </flux:modal>
      
      <!-- Task Detail Modal -->
      <flux:modal name="task-detail" class="max-w-2xl">
          <livewire:tasks.detail />
      </flux:modal>
  </div>
  
  @script
  <script>
  Alpine.data('kanbanBoard', () => ({
      initColumn(el) {
          window.initSortable(el, {
              onReorder: (taskId, newStatus, newIndex) => {
                  $wire.dispatch('task-reordered', { 
                      taskId: parseInt(taskId), 
                      newStatus: newStatus, 
                      newPosition: newIndex 
                  });
              }
          });
      }
  }));
  </script>
  @endscript
  ```

### 8.5 Task Card Component

- [ ] `resources/views/livewire/tasks/card.blade.php`:
  ```php
  <?php
  use Livewire\Volt\Component;
  
  new class extends Component {
      public Task $task;
      
      public function openDetail(): void
      {
          $this->dispatch('open-task-detail', id: $this->task->id);
          $this->dispatch('open-modal', name: 'task-detail');
      }
  }; ?>
  
  <div 
      wire:key="task-card-{{ $task->id }}"
      data-task-id="{{ $task->id }}"
      class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm p-3 cursor-pointer hover:shadow-md transition-shadow"
      wire:click="openDetail"
  >
      <!-- Drag Handle -->
      <div class="drag-handle absolute top-2 right-2 cursor-grab">
          <flux:icon name="bars-3" class="w-4 h-4 text-zinc-400" />
      </div>
      
      <!-- Priority Badge -->
      <flux:badge size="sm" :color="$task->priority->color()" class="mb-2">
          {{ $task->priority->label() }}
      </flux:badge>
      
      <!-- Title -->
      <h4 class="font-medium text-sm line-clamp-2 mb-2">
          {{ $task->title }}
      </h4>
      
      <!-- Meta -->
      <div class="flex items-center justify-between text-xs text-zinc-500">
          <div class="flex items-center gap-2">
              <!-- Due Date -->
              @if($task->due_date)
              <span class="{{ $task->due_date->isPast() ? 'text-red-500' : '' }}">
                  <flux:icon name="calendar" class="w-3 h-3 inline" />
                  {{ $task->due_date->format('d M') }}
              </span>
              @endif
              
              <!-- Comments Count -->
              @if($task->comments_count > 0)
              <span>
                  <flux:icon name="chat-bubble-left" class="w-3 h-3 inline" />
                  {{ $task->comments_count }}
              </span>
              @endif
              
              <!-- Checklist Progress -->
              @if(count($task->checklist ?? []) > 0)
              @php
                  $completed = collect($task->checklist)->where('completed', true)->count();
                  $total = count($task->checklist);
              @endphp
              <span>
                  <flux:icon name="check-circle" class="w-3 h-3 inline" />
                  {{ $completed }}/{{ $total }}
              </span>
              @endif
          </div>
          
          <!-- Assignee -->
          @if($task->assignee)
          <flux:avatar size="xs" :src="$task->assignee->avatar" :name="$task->assignee->name" />
          @endif
      </div>
  </div>
  ```

### 8.6 Task Form Component

- [ ] `resources/views/livewire/tasks/form.blade.php`:
  ```php
  <?php
  use Livewire\Volt\Component;
  use Livewire\Attributes\On;
  use App\Data\TaskData;
  use App\Actions\Tasks\CreateTaskAction;
  use App\Actions\Tasks\UpdateTaskAction;
  
  new class extends Component {
      public ?Task $task = null;
      
      public string $title = '';
      public ?string $description = null;
      public string $status = 'backlog';
      public string $priority = 'medium';
      public ?string $due_date = null;
      public ?float $estimated_hours = null;
      public ?int $assignee_id = null;
      public ?int $contact_id = null;
      public ?int $appointment_id = null;
      
      #[On('edit-task')]
      public function loadTask(int $id): void
      {
          $this->task = Task::findOrFail($id);
          $this->fill($this->task->toArray());
          $this->due_date = $this->task->due_date?->format('Y-m-d');
      }
      
      #[On('quick-create')]
      public function quickCreate(string $status): void
      {
          $this->reset();
          $this->status = $status;
      }
      
      public function save(
          CreateTaskAction $createAction,
          UpdateTaskAction $updateAction
      ): void {
          $data = TaskData::validateAndCreate([
              'title' => $this->title,
              'description' => $this->description,
              'status' => TaskStatus::from($this->status),
              'priority' => TaskPriority::from($this->priority),
              'due_date' => $this->due_date ? Carbon::parse($this->due_date) : null,
              'estimated_hours' => $this->estimated_hours,
              'assignee_id' => $this->assignee_id,
              'contact_id' => $this->contact_id,
              'appointment_id' => $this->appointment_id,
          ]);
          
          if ($this->task) {
              $updateAction->execute($this->task, $data);
          } else {
              $task = $createAction->execute($data, auth()->user());
              
              // Atama bildirimi gönder (Faz 9)
              if ($task->assignee_id && $task->assignee_id !== auth()->id()) {
                  // $task->assignee->notify(new TaskAssignedNotification($task));
              }
          }
          
          $this->dispatch('task-saved');
          $this->dispatch('close-modal');
          $this->reset();
      }
      
      #[Computed]
      public function teamMembers(): Collection
      {
          return User::where('company_id', auth()->user()->company_id)->get();
      }
      
      #[Computed]
      public function contacts(): Collection
      {
          return Contact::where('company_id', auth()->user()->company_id)->limit(50)->get();
      }
  }; ?>
  
  <div class="p-6">
      <flux:heading size="lg" class="mb-6">
          {{ $task ? 'Görevi Düzenle' : 'Yeni Görev' }}
      </flux:heading>
      
      <form wire:submit="save" class="space-y-4">
          <flux:input wire:model.blur="title" label="Başlık" required />
          
          <flux:textarea wire:model.blur="description" label="Açıklama" rows="3" />
          
          <div class="grid grid-cols-2 gap-4">
              <flux:select wire:model="status" label="Durum">
                  @foreach(TaskStatus::cases() as $s)
                      <option value="{{ $s->value }}">{{ $s->label() }}</option>
                  @endforeach
              </flux:select>
              
              <flux:select wire:model="priority" label="Öncelik">
                  @foreach(TaskPriority::cases() as $p)
                      <option value="{{ $p->value }}">{{ $p->label() }}</option>
                  @endforeach
              </flux:select>
          </div>
          
          <div class="grid grid-cols-2 gap-4">
              <flux:input type="date" wire:model="due_date" label="Bitiş Tarihi" />
              <flux:input type="number" step="0.5" wire:model="estimated_hours" label="Tahmini Süre (saat)" />
          </div>
          
          <flux:select wire:model="assignee_id" label="Atanan Kişi">
              <option value="">Atanmadı</option>
              @foreach($this->teamMembers as $member)
                  <option value="{{ $member->id }}">{{ $member->name }}</option>
              @endforeach
          </flux:select>
          
          <flux:select wire:model="contact_id" label="İlişkili Kişi">
              <option value="">Yok</option>
              @foreach($this->contacts as $contact)
                  <option value="{{ $contact->id }}">{{ $contact->full_name }}</option>
              @endforeach
          </flux:select>
          
          <div class="flex justify-end gap-2 pt-4">
              <flux:button variant="ghost" x-on:click="$flux.close()">İptal</flux:button>
              <flux:button type="submit" variant="primary">
                  {{ $task ? 'Güncelle' : 'Oluştur' }}
              </flux:button>
          </div>
      </form>
  </div>
  ```

### 8.7 Task Detail Component (Comments + Checklist)

- [ ] `resources/views/livewire/tasks/detail.blade.php`:
  ```php
  <?php
  use Livewire\Volt\Component;
  use Livewire\Attributes\On;
  use App\Actions\Tasks\AddTaskCommentAction;
  use App\Actions\Tasks\DeleteTaskAction;
  
  new class extends Component {
      public ?Task $task = null;
      public string $newComment = '';
      public string $newChecklistItem = '';
      
      #[On('open-task-detail')]
      public function loadTask(int $id): void
      {
          $this->task = Task::with(['assignee', 'contact', 'comments.user'])->findOrFail($id);
      }
      
      public function addComment(AddTaskCommentAction $action): void
      {
          $this->validate(['newComment' => 'required|min:2']);
          
          $action->execute($this->task, auth()->user(), $this->newComment);
          $this->newComment = '';
          $this->task->refresh();
      }
      
      public function addChecklistItem(): void
      {
          if (!$this->newChecklistItem) return;
          
          $checklist = $this->task->checklist ?? [];
          $checklist[] = ['text' => $this->newChecklistItem, 'completed' => false];
          
          $this->task->update(['checklist' => $checklist]);
          $this->newChecklistItem = '';
      }
      
      public function toggleChecklistItem(int $index): void
      {
          $checklist = $this->task->checklist;
          $checklist[$index]['completed'] = !$checklist[$index]['completed'];
          
          $this->task->update(['checklist' => $checklist]);
      }
      
      public function removeChecklistItem(int $index): void
      {
          $checklist = $this->task->checklist;
          unset($checklist[$index]);
          
          $this->task->update(['checklist' => array_values($checklist)]);
      }
      
      public function delete(DeleteTaskAction $action): void
      {
          $this->authorize('delete', $this->task);
          $action->execute($this->task);
          
          $this->dispatch('task-deleted');
          $this->dispatch('close-modal');
      }
  }; ?>
  
  @if($task)
  <div class="p-6">
      <!-- Header -->
      <div class="flex justify-between items-start mb-4">
          <div>
              <flux:heading size="lg">{{ $task->title }}</flux:heading>
              <div class="flex gap-2 mt-2">
                  <flux:badge :color="$task->status->color()">{{ $task->status->label() }}</flux:badge>
                  <flux:badge :color="$task->priority->color()">{{ $task->priority->label() }}</flux:badge>
              </div>
          </div>
          <div class="flex gap-2">
              <flux:button variant="outline" wire:click="$dispatch('edit-task', { id: {{ $task->id }} })" icon="pencil">
                  Düzenle
              </flux:button>
              @can('delete', $task)
              <flux:button variant="danger" wire:click="delete" icon="trash">
                  Sil
              </flux:button>
              @endcan
          </div>
      </div>
      
      <!-- Description -->
      @if($task->description)
      <div class="prose dark:prose-invert max-w-none mb-6">
          {!! Str::markdown($task->description) !!}
      </div>
      @endif
      
      <!-- Checklist -->
      <div class="mb-6">
          <flux:heading size="sm" class="mb-3">Checklist</flux:heading>
          
          @if($task->checklist)
          @php
              $completed = collect($task->checklist)->where('completed', true)->count();
              $total = count($task->checklist);
          @endphp
          <div class="mb-2 text-sm text-zinc-500">{{ $completed }}/{{ $total }} tamamlandı</div>
          @endif
          
          <div class="space-y-2 mb-3">
              @foreach($task->checklist ?? [] as $index => $item)
              <div class="flex items-center gap-2">
                  <flux:checkbox 
                      :checked="$item['completed']" 
                      wire:click="toggleChecklistItem({{ $index }})"
                  />
                  <span class="{{ $item['completed'] ? 'line-through text-zinc-400' : '' }}">
                      {{ $item['text'] }}
                  </span>
                  <flux:button variant="ghost" size="sm" wire:click="removeChecklistItem({{ $index }})">
                      <flux:icon name="x-mark" class="w-3 h-3" />
                  </flux:button>
              </div>
              @endforeach
          </div>
          
          <div class="flex gap-2">
              <flux:input wire:model="newChecklistItem" placeholder="Yeni madde..." class="flex-1" wire:keydown.enter="addChecklistItem" />
              <flux:button wire:click="addChecklistItem" variant="outline">Ekle</flux:button>
          </div>
      </div>
      
      <!-- Comments -->
      <div>
          <flux:heading size="sm" class="mb-3">Yorumlar</flux:heading>
          
          <div class="space-y-4 mb-4 max-h-64 overflow-y-auto">
              @forelse($task->comments as $comment)
              <div class="flex gap-3">
                  <flux:avatar size="sm" :src="$comment->user->avatar" />
                  <div>
                      <div class="flex items-center gap-2">
                          <span class="font-medium text-sm">{{ $comment->user->name }}</span>
                          <span class="text-xs text-zinc-500">{{ $comment->created_at->diffForHumans() }}</span>
                      </div>
                      <p class="text-sm mt-1">{{ $comment->body }}</p>
                  </div>
              </div>
              @empty
              <flux:text class="text-zinc-500">Henüz yorum yok.</flux:text>
              @endforelse
          </div>
          
          <div class="flex gap-2">
              <flux:input wire:model="newComment" placeholder="Yorum ekle..." class="flex-1" />
              <flux:button wire:click="addComment" variant="primary">Gönder</flux:button>
          </div>
      </div>
  </div>
  @endif
  ```

### 8.8 TaskPolicy

- [ ] `app/Policies/TaskPolicy.php`:
  ```php
  class TaskPolicy
  {
      public function viewAny(User $user): bool
      {
          return true;
      }
      
      public function view(User $user, Task $task): bool
      {
          if ($user->hasAnyRole(['admin', 'manager'])) {
              return $task->company_id === $user->company_id;
          }
          
          return $task->assignee_id === $user->id || $task->created_by === $user->id;
      }
      
      public function create(User $user): bool
      {
          return true;
      }
      
      public function update(User $user, Task $task): bool
      {
          if ($user->hasAnyRole(['admin', 'manager'])) {
              return $task->company_id === $user->company_id;
          }
          
          return $task->created_by === $user->id;
      }
      
      public function delete(User $user, Task $task): bool
      {
          return $user->hasAnyRole(['admin', 'manager']) 
              && $task->company_id === $user->company_id;
      }
      
      public function assign(User $user, Task $task): bool
      {
          return $user->hasAnyRole(['admin', 'manager']);
      }
  }
  ```

---

## Doğrulama

```bash
php artisan test --filter=Task
```

Manuel test:
1. Görevler sayfasına git → 5 sütunlu Kanban
2. "+" butonu → Quick create form
3. Görev oluştur → Doğru sütunda görünsün
4. Görevi sürükle → Başka sütuna taşı → Status güncellensin
5. Aynı sütunda sürükle → Position güncellensin
6. Göreve tıkla → Detail modal
7. Checklist ekle → Toggle çalışsın
8. Yorum yaz → Listede görünsün
9. "Bana Atananlar" filtresi çalışsın

---

## Dosya Listesi

```
app/
├── Actions/Tasks/
│   ├── CreateTaskAction.php
│   ├── UpdateTaskAction.php
│   ├── DeleteTaskAction.php
│   ├── ReorderTaskAction.php
│   └── AddTaskCommentAction.php
├── Data/
│   └── TaskData.php
├── Models/
│   ├── Task.php (güncelle - checklist cast)
│   └── TaskComment.php
├── Policies/
│   └── TaskPolicy.php
└── Enums/
    ├── TaskStatus.php (label(), color() metodları)
    └── TaskPriority.php (label(), color() metodları)

resources/
├── js/
│   └── sortable.js
└── views/livewire/tasks/
    ├── board.blade.php
    ├── card.blade.php
    ├── form.blade.php
    └── detail.blade.php
```

---

## Notlar

- **Checklist** JSONB sütununda saklanır (ayrı tablo değil)
- **Sortable.js** Alpine + Livewire hybrid pattern
- **Optimistic UI** - Sürükle-bırak anında görsel güncelleme
- **Position** sütunu sıralama için kritik
- Projeler (task grouping) Post-MVP
- Time tracking Post-MVP
- Subtasks (full task olarak) Post-MVP
