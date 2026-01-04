<?php

declare(strict_types=1);

namespace App\Models;

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
    }

    /**
     * Remove user from company.
     */
    public function removeUser(User $user): void
    {
        // Remove Spatie roles first
        setPermissionsTeamId($this->id);
        $user->syncRoles([]);

        // Then detach from pivot
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
