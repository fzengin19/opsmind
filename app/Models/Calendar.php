<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CalendarType;
use App\Enums\CalendarVisibility;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Calendar extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'name',
        'color',
        'type',
        'visibility',
        'is_default',
        'google_calendar_id',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'type' => CalendarType::class,
            'visibility' => CalendarVisibility::class,
            'is_default' => 'boolean',
            'settings' => 'array',
        ];
    }

    // ==========================================
    // Relationships
    // ==========================================

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    // ==========================================
    // Scopes
    // ==========================================

    public function scopeForCompany(Builder $query, int $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeAccessibleBy(Builder $query, User $user): Builder
    {
        $companyId = $user->currentCompany()?->id;

        return $query->where(function ($q) use ($companyId) {
            $q->where('visibility', CalendarVisibility::CompanyWide)
                ->where('company_id', $companyId);
        })->orWhereHas('users', fn ($uq) => $uq->where('user_id', $user->id));
    }

    // ==========================================
    // Helpers
    // ==========================================

    public function isAccessibleBy(User $user): bool
    {
        if ($this->visibility === CalendarVisibility::CompanyWide
            && $this->company_id === $user->currentCompany()?->id) {
            return true;
        }

        return $this->users()->where('user_id', $user->id)->exists();
    }
}
