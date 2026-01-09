<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AppointmentType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Appointment extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'calendar_id',
        'title',
        'description',
        'type',
        'start_at',
        'end_at',
        'all_day',
        'location',
        'color',
        'google_calendar_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'type' => AppointmentType::class,
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'all_day' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function calendar(): BelongsTo
    {
        return $this->belongsTo(Calendar::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function attendees(): HasMany
    {
        return $this->hasMany(AppointmentAttendee::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }
}
