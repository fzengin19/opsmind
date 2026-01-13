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
                'dayName' => $current->dayName, // or shortDayName
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
                'dayName' => $day->isoFormat('ddd'), // Pzt, Sal
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
    public function getAppointmentsForWeek(Carbon $date, int $companyId, ?array $calendarIds = null): Collection
    {
        $weekStart = $date->copy()->startOfWeek(Carbon::MONDAY);
        $weekEnd = $weekStart->copy()->endOfWeek(Carbon::SUNDAY)->endOfDay();

        $query = Appointment::where('company_id', $companyId)
            ->whereBetween('start_at', [$weekStart, $weekEnd]);

        if ($calendarIds !== null) {
            $query->whereIn('calendar_id', $calendarIds);
        }

        $appointments = $query->orderBy('start_at')
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
                    'endHour' => $apt->end_at->hour,
                    'endMinute' => $apt->end_at->minute,
                    'durationMinutes' => $apt->start_at->diffInMinutes($apt->end_at),
                    'color' => $apt->calendar?->color ?? $apt->type->color(),
                    'type' => $apt->type->value,
                    'calendarId' => $apt->calendar_id,
                    'isAllDay' => $apt->all_day,
                    // Default values, will be recalculated for overlaps
                    'width' => 100,
                    'left' => 0,
                ];
            });

        // Calculate overlaps
        $appointments = $appointments->keyBy('id')->toArray(); // Use keyBy for direct access
        $byDay = collect($appointments)->groupBy('dayIndex');

        foreach ($byDay as $dayIndex => $dayEvents) {
            $dayEvents = $dayEvents->sortBy('startHour')->values();
            $clusters = [];

            // Group overlapping events
            foreach ($dayEvents as $event) {
                $placed = false;
                foreach ($clusters as &$cluster) {
                    // Check if this event overlaps with the cluster's time range
                    $clusterStart = $cluster['start'];
                    $clusterEnd = $cluster['end'];
                    $eventStart = ($event['startHour'] * 60) + $event['startMinute'];
                    $eventEnd = $eventStart + $event['durationMinutes'];

                    if ($eventStart < $clusterEnd) { // Overlap detected
                        $cluster['events'][] = $event['id'];
                        $cluster['end'] = max($clusterEnd, $eventEnd);
                        $placed = true;
                        break;
                    }
                }
                unset($cluster); // Break reference

                if (!$placed) {
                    $clusters[] = [
                        'start' => ($event['startHour'] * 60) + $event['startMinute'],
                        'end' => ($event['startHour'] * 60) + $event['startMinute'] + $event['durationMinutes'],
                        'events' => [$event['id']],
                    ];
                }
            }

            // Assign width and left based on clusters
            foreach ($clusters as $cluster) {
                $count = count($cluster['events']);
                foreach ($cluster['events'] as $index => $eventId) {
                    if (isset($appointments[$eventId])) {
                        $appointments[$eventId]['width'] = 100 / $count;
                        $appointments[$eventId]['left'] = ($index * (100 / $count));
                    }
                }
            }
        }

        return collect($appointments)->values(); // Return as updated collection
    }

    /**
     * Get agenda for a specific day with "Stack & Gap" logic.
     * Returns a mixed collection of Events and Gaps.
     */
    public function getDayAgenda(Carbon $date, int $companyId, ?array $calendarIds = null): Collection
    {
        $query = Appointment::where('company_id', $companyId)
            ->whereDate('start_at', $date->toDateString());

        if ($calendarIds !== null) {
            $query->whereIn('calendar_id', $calendarIds);
        }

        // 1. Fetch & Sort
        $events = $query->with('calendar')
            ->orderBy('start_at')
            ->orderBy('end_at')
            ->orderBy('created_at')
            ->get();

        $agenda = collect();
        // Start tracking from 08:00 or the start of the first event if earlier
        $cursor = $date->copy()->setTime(8, 0); 
        
        // If first event is earlier than 08:00, start cursor there
        if ($events->isNotEmpty() && $events->first()->start_at->lt($cursor)) {
            $cursor = $events->first()->start_at->copy();
        }

        // 2. Stack & Gap Algorithm
        foreach ($events as $event) {
            // Check for Gap (> 15 mins)
            // We only look for "Absolute Free Time". 
            // If the cursor (max end time seen so far) is less than this event's start...
            if ($cursor->copy()->addMinutes(15)->lte($event->start_at)) {
                $agenda->push([
                    'type' => 'gap',
                    'start' => $cursor->copy(),
                    'end' => $event->start_at->copy(),
                    'duration' => $cursor->diffInMinutes($event->start_at),
                ]);
            }

            // Add Event
            $agenda->push([
                'type' => 'event',
                'data' => $event,
            ]);

            // Update Cursor (Max End Time)
            // We must take the MAX of current cursor and event end, to handle nested events.
            // If we just took event->end, a short nested event could pull the cursor back.
            if ($event->end_at->gt($cursor)) {
                $cursor = $event->end_at->copy();
            }
        }

        // Optional: specific end of day? For now let's just leave it open-ended.
        
        return $agenda;
    }



    /**
     * Get appointments for a specific month (grouped by date)
     */
    public function getAppointmentsForMonth(Carbon $date, int $companyId, ?array $calendarIds = null): array
    {
        $startOfMonth = $date->copy()->startOfMonth()->startOfWeek(Carbon::MONDAY);
        $endOfMonth = $date->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY)->endOfDay();

        // 1. Fetch all events overlapping the month window
        $query = Appointment::where('company_id', $companyId)
            ->where(function ($q) use ($startOfMonth, $endOfMonth) {
                // Overlap: (Event Start <= Window End) AND (Event End >= Window Start)
                $q->where('start_at', '<=', $endOfMonth)
                  ->where('end_at', '>=', $startOfMonth);
            });

        if ($calendarIds !== null) {
            $query->whereIn('calendar_id', $calendarIds);
        }

        $appointments = $query->orderBy('start_at')
            ->orderBy('all_day', 'desc') // Put all-day events first
            ->with('calendar')
            ->get();

        $grouped = [];

        foreach ($appointments as $apt) {
            // Determine effective range within the grid
            $effectiveStart = $apt->start_at->max($startOfMonth);
            $effectiveEnd = $apt->end_at->min($endOfMonth);
            
            // Iterate through every day of the event
            $current = $effectiveStart->copy()->startOfDay();
            $lastDay = $effectiveEnd->copy()->startOfDay(); // Comparison target based on date only

            // Loop until we pass the last day of the event (or the grid)
            while ($current->lte($lastDay)) {
                $dateKey = $current->toDateString();

                if (! isset($grouped[$dateKey])) {
                    $grouped[$dateKey] = [];
                }

                // Determine Position Metadata
                $isStartDay = $current->isSameDay($apt->start_at);
                $isEndDay = $current->isSameDay($apt->end_at);
                $isSingleDay = $isStartDay && $isEndDay;

                $position = 'single';
                if (!$isSingleDay) {
                    if ($isStartDay) $position = 'start';
                    elseif ($isEndDay) $position = 'end';
                    else $position = 'middle';
                }

                $grouped[$dateKey][] = [
                    'id' => $apt->id,
                    'title' => $apt->title,
                    'time' => $apt->start_at->format('H:i'),
                    'color' => $apt->calendar?->color ?? $apt->type->color(),
                    'calendarId' => $apt->calendar_id,
                    'isAllDay' => $apt->all_day,
                    'position' => $position, // 'single', 'start', 'end', 'middle'
                ];

                $current->addDay();
            }
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
