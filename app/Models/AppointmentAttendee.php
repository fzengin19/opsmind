<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AttendeeStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppointmentAttendee extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'appointment_id',
        'user_id',
        'contact_id',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => AttendeeStatus::class,
            'created_at' => 'datetime',
        ];
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }
}
