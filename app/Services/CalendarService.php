<?php

namespace App\Services;

use App\Enums\AppointmentType;
use App\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class CalendarService
{
    /**
     * Generate the grid for the month view (including previous/next month days)
     */
    public function getMonthGrid(Carbon $date): array
    {
        $startOfMonth = $date->copy()->startOfMonth();
        $endOfMonth = $date->copy()->endOfMonth();

        // Start from the beginning of the week (Monday)
        $startOfGrid = $startOfMonth->copy()->startOfWeek(Carbon::MONDAY);
        // End at the end of the week (Sunday), ensuring we cover the whole month
        $endOfGrid = $endOfMonth->copy()->endOfWeek(Carbon::SUNDAY);

        $days = [];
        $current = $startOfGrid->copy();

        while ($current->lte($endOfGrid)) {
            $days[] = [
                'date' => $current->copy(),
                'isCurrentMonth' => $current->month === $date->month,
                'isToday' => $current->isToday(),
                'dayName' => $current->locale('tr')->dayName, // or shortDayName
                'day' => $current->day,
            ];
            $current->addDay();
        }

        return $days;
    }

    /**
     * Generate the grid for the week view (7 days)
     */
    public function getWeekGrid(Carbon $date): array
    {
        $startOfWeek = $date->copy()->startOfWeek(Carbon::MONDAY);
        $days = [];

        for ($i = 0; $i < 7; $i++) {
            $day = $startOfWeek->copy()->addDays($i);
            $days[] = [
                'date' => $day,
                'isToday' => $day->isToday(),
                'dayName' => $day->locale('tr')->isoFormat('ddd'), // Pzt, Sal
                'day' => $day->day,
            ];
        }

        return $days;
    }

    /**
     * Generate time slots for the day/week view
     */
    public function getTimeSlots(int $startHour = 0, int $endHour = 23, int $intervalMinutes = 60): array
    {
        $slots = [];
        for ($hour = $startHour; $hour <= $endHour; $hour++) {
            $slots[] = sprintf('%02d:00', $hour);
        }

        return $slots;
    }

    /**
     * Get appointments for a specific week
     */
    public function getAppointmentsForWeek(Carbon $date, int $companyId, ?int $calendarId = null): Collection
    {
        $weekStart = $date->copy()->startOfWeek(Carbon::MONDAY);
        $weekEnd = $weekStart->copy()->endOfWeek(Carbon::SUNDAY)->endOfDay();

        $query = Appointment::where('company_id', $companyId)
            ->whereBetween('start_at', [$weekStart, $weekEnd]);

        if ($calendarId !== null) {
            $query->where('calendar_id', $calendarId);
        }

        return $query->orderBy('start_at')
            ->with('calendar')
            ->get()
            ->map(function ($apt) use ($weekStart) {
                $dayIndex = (int) $weekStart->diffInDays($apt->start_at);

                return [
                    'id' => $apt->id,
                    'title' => $apt->title,
                    'dayIndex' => $dayIndex,
                    'startHour' => $apt->start_at->hour,
                    'startMinute' => $apt->start_at->minute,
                    'durationMinutes' => $apt->start_at->diffInMinutes($apt->end_at),
                    'color' => $apt->calendar?->color ?? $apt->type->color(),
                    'type' => $apt->type->value,
                    'calendarId' => $apt->calendar_id,
                ];
            });
    }

    /**
     * Get appointments for a specific month (grouped by date)
     */
    public function getAppointmentsForMonth(Carbon $date, int $companyId, ?int $calendarId = null): array
    {
        $startOfMonth = $date->copy()->startOfMonth()->startOfWeek(Carbon::MONDAY);
        $endOfMonth = $date->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY)->endOfDay();

        $query = Appointment::where('company_id', $companyId)
            ->whereBetween('start_at', [$startOfMonth, $endOfMonth]);

        if ($calendarId !== null) {
            $query->where('calendar_id', $calendarId);
        }

        $appointments = $query->orderBy('start_at')->with('calendar')->get();

        $grouped = [];
        foreach ($appointments as $apt) {
            $dateKey = $apt->start_at->toDateString(); // Y-m-d
            if (! isset($grouped[$dateKey])) {
                $grouped[$dateKey] = [];
            }
            $grouped[$dateKey][] = [
                'id' => $apt->id,
                'title' => $apt->title,
                'time' => $apt->start_at->format('H:i'),
                'color' => $apt->calendar?->color ?? $apt->type->color(),
                'calendarId' => $apt->calendar_id,
            ];
        }

        return $grouped;
    }

    /**
     * Map AppointmentType to calendar color
     */
    private function mapTypeToColor(AppointmentType $type): string
    {
        return match ($type) {
            AppointmentType::Meeting => 'primary',
            AppointmentType::Call => 'success',
            AppointmentType::Focus => 'warning',
            AppointmentType::Break => 'zinc',
        };
    }

    /**
     * Calculate vertical position and height for week view events
     * Returns: ['top' => '10%', 'height' => '50px']
     */
    public function calculateEventStyle(Carbon $eventStart, Carbon $eventEnd, int $startHour = 0): array
    {
        $startMinutes = ($eventStart->hour * 60) + $eventStart->minute;
        $endMinutes = ($eventEnd->hour * 60) + $eventEnd->minute;

        // Offset from the calendar start time
        $offsetMinutes = $startMinutes - ($startHour * 60);

        // 1 hour = 60px (assumption for now)
        $pixelsPerMinute = 1;

        return [
            'top' => ($offsetMinutes * $pixelsPerMinute).'px',
            'height' => max(30, ($endMinutes - $startMinutes) * $pixelsPerMinute).'px',
        ];
    }
}
