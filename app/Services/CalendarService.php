<?php

namespace App\Services;

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
            'top' => ($offsetMinutes * $pixelsPerMinute) . 'px',
            'height' => max(30, ($endMinutes - $startMinutes) * $pixelsPerMinute) . 'px',
        ];
    }
}
