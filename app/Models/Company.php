<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CompanyRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
            ->withPivot('role', 'department_id', 'job_title', 'joined_at')
            ->withTimestamps();
    }

    /**
     * Get company owner(s).
     */
    public function owners(): BelongsToMany
    {
        return $this->users()->wherePivot('role', CompanyRole::Owner->value);
    }

    /**
     * Get company admins.
     */
    public function admins(): BelongsToMany
    {
        return $this->users()->wherePivotIn('role', [
            CompanyRole::Owner->value,
            CompanyRole::Admin->value,
        ]);
    }

    /**
     * Add user to company with role.
     */
    public function addUser(User $user, CompanyRole $role, ?int $departmentId = null, ?string $jobTitle = null): void
    {
        $this->users()->attach($user->id, [
            'role' => $role->value,
            'department_id' => $departmentId,
            'job_title' => $jobTitle,
            'joined_at' => now(),
        ]);
    }

    /**
     * Remove user from company.
     */
    public function removeUser(User $user): void
    {
        $this->users()->detach($user->id);
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

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }
}
