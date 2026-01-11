# Calendar Day Detail & Overlay Design (Phase 6)

## 1. Overview
This document outlines the implementation for the **Monthly View Day Detail Overlay**, replacing complex weekly grids with a contextual "Daily Agenda".

## 2. Core Requirements
- **Contextual Overlay:** Absolute positioning over the calendar grid.
- **Stack & Gap Layout:** Strict vertical stacking (no visual overlap). explicit "Free Time" dividers.
- **Responsive:** Desktop Grid (Masonry-like) vs Mobile Stack.

## 3. Interaction Strategy (Add/Edit)

### A. Global "New Appointment" Button (Top Right)
- **Decision:** **KEEP**.
- **Reason:** Users often need to add events for dates *other* than the one they are looking at, or quick-add without drilling down.

### B. Day-Specific "Add" Button (In Overlay)
- **Behavior:** Opens the form pre-filled with the **selected day** as the `start_at` date.
- **Multi-day Logic:**
    - User clicks "Add" on **Jan 12**.
    - Form opens: `Start: Jan 12, 09:00` | `End: Jan 12, 10:00`.
    - **Scenario (3-day Vacation):** User simply clicks the **End Date** picker in the form and changes it to **Jan 15**.
    - **Result:** No special "Date Range Picker" needed before the modal. The Modal *is* the picker.

## 4. Architecture

### A. Backend (`CalendarService.php`)
**Method:** `getDayAgenda(Carbon $date, int $companyId)`
- **Sorting:** Start(ASC) -> End(ASC) -> Created(ASC).
- **Gap Injection:**
    - Traverse events.
    - If `event->start > max_cursor + 15min` -> Insert `Gap` object.
    - `max_cursor` tracks the furthest end time seen so far (handling nested events).

### B. Frontend (`livewire:calendar.day-detail`)
**Type:** Livewire Volt.
- **Layout:** Grid (Ghost Cards for Gaps).
- **State:** `$date`, `$agenda` (Computed).
- **Listeners:** `open-day-detail`, `appointment-saved`.

### C. Integration
- `index.blade.php` renders the component inside an `x-show` overlay.
- Week/Day toggle buttons hidden.
