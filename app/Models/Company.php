<?php

declare(strict_types=1);

namespace App\Models;

use App\Observers\CompanyObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        app(\App\Actions\Users\AddUserToCompanyAction::class)->execute(
            $this,
            $user,
            $roleName,
            $departmentId,
            $jobTitle
        );
    }

    /**
     * Remove user from company.
     */
    public function removeUser(User $user): void
    {
        app(\App\Actions\Users\RemoveUserFromCompanyAction::class)->execute($this, $user);
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
