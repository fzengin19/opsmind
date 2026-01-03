<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CompanyRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, HasRoles, Notifiable, TwoFactorAuthenticatable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar',
        'phone',
        'timezone',
        'google_id',
    ];

    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // ==========================================
    // Company Relationships (Pivot)
    // ==========================================

    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class, 'company_user')
            ->withPivot('role', 'department_id', 'job_title', 'joined_at')
            ->withTimestamps();
    }

    /**
     * Get user's current/default company.
     * MVP: User has only one company.
     */
    public function currentCompany(): ?Company
    {
        return $this->companies()->first();
    }

    /**
     * Check if user belongs to any company.
     */
    public function hasCompany(): bool
    {
        return $this->companies()->exists();
    }

    /**
     * Get user's role in a specific company.
     */
    public function roleIn(Company $company): ?CompanyRole
    {
        $pivot = $this->companies()->where('company_id', $company->id)->first()?->pivot;

        return $pivot ? CompanyRole::from($pivot->role) : null;
    }

    /**
     * Check if user is owner of a company.
     */
    public function isOwnerOf(Company $company): bool
    {
        return $this->roleIn($company) === CompanyRole::Owner;
    }

    /**
     * Get user's department in current company.
     */
    public function currentDepartment(): ?Department
    {
        $pivot = $this->companies()->first()?->pivot;

        if (! $pivot || ! $pivot->department_id) {
            return null;
        }

        return Department::find($pivot->department_id);
    }

    /**
     * Get user's job title in current company.
     */
    public function currentJobTitle(): ?string
    {
        return $this->companies()->first()?->pivot?->job_title;
    }

    // ==========================================
    // Other Relationships
    // ==========================================

    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class, 'created_by');
    }

    public function createdAppointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'created_by');
    }

    public function appointments(): BelongsToMany
    {
        return $this->belongsToMany(Appointment::class, 'appointment_attendees')
            ->withPivot('status')
            ->withTimestamps();
    }

    public function assignedTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'assignee_id');
    }

    public function createdTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'created_by');
    }

    public function taskComments(): HasMany
    {
        return $this->hasMany(TaskComment::class);
    }

    // ==========================================
    // Helpers
    // ==========================================

    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }
}
