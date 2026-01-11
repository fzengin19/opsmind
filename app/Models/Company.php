<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CalendarType;
use App\Enums\CalendarVisibility;
use App\Observers\CompanyObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Calendar;

#[ObservedBy([CompanyObserver::class])]
class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'logo',
        'timezone',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
        ];
    }

    // ==========================================
    // User Relationships (Pivot)
    // ==========================================

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'company_user')
            ->withPivot('department_id', 'job_title', 'joined_at')
            ->withTimestamps();
    }

    /**
     * Get company owners (users with Sahip role).
     */
    public function owners()
    {
        setPermissionsTeamId($this->id);

        return $this->users()->whereHas('roles', fn ($q) => $q->where('name', 'owner'));
    }

    /**
     * Add user to company with Spatie role.
     */
    public function addUser(User $user, string $roleName, ?int $departmentId = null, ?string $jobTitle = null): void
    {
        $this->users()->attach($user->id, [
            'department_id' => $departmentId,
            'job_title' => $jobTitle,
            'joined_at' => now(),
        ]);

        setPermissionsTeamId($this->id);
        $user->assignRole($roleName);

        // Kullanıcıya kişisel takvim oluştur
        $calendar = $this->calendars()->create([
            'name' => $user->name.' '.__('calendar.personal_calendar_suffix'),
            'type' => CalendarType::Personal->value,
            'visibility' => CalendarVisibility::Private->value,
            'is_default' => false,
            'color' => '#8b5cf6',
        ]);

        $calendar->users()->attach($user->id, ['role' => 'owner']);
    }

    /**
     * Remove user from company.
     */
    public function removeUser(User $user): void
    {
        // Remove Spatie roles first
        setPermissionsTeamId($this->id);
        $user->syncRoles([]);

        // Delete user's personal calendar in this company
        $this->calendars()
            ->where('type', CalendarType::Personal->value)
            ->whereHas('users', fn ($q) => $q->where('user_id', $user->id)->where('role', 'owner'))
            ->delete();

        // Detach user from all company calendars
        $calendarIds = $this->calendars()->pluck('id');
        $user->calendars()->detach($calendarIds);

        // Then detach from company pivot
        $this->users()->detach($user->id);
    }

    // ==========================================
    // Calendar Relationships
    // ==========================================

    public function calendars(): HasMany
    {
        return $this->hasMany(Calendar::class);
    }

    public function defaultCalendar(): ?Calendar
    {
        return $this->calendars()->where('is_default', true)->first();
    }

    // ==========================================
    // Other Relationships
    // ==========================================

    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }





    public function invitations(): HasMany
    {
        return $this->hasMany(Invitation::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }
}
