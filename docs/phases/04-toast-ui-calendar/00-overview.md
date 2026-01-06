# Phase 4: Calendar UI (TOAST UI) - Comprehensive Plan

**Status:** Draft
**Target:** Interactive Calendar Module
**Stack:** Laravel 12 (Volt), Livewire 3, Alpine.js, Tailwind CSS v4, Flux UI, TOAST UI Calendar v2.1

## 🏛 Architectural Principles

1.  **Strict Isolation (Livewire vs DOM):**
    The calendar container MUST use `wire:ignore`. Livewire NEVER touches the internal DOM of the calendar. All updates happen via the Alpine.js Bridge.

2.  **Alpine.js Bridge Pattern:**
    We will use a dedicated `CalendarManager` class in JavaScript (Alpine.data) that acts as a bridge.
    - **Downstream (PHP -> JS):** Livewire dispatches browser events (e.g., `calendar-refresh`), Alpine listens and calls TOAST UI API.
    - **Upstream (JS -> PHP):** TOAST UI events (e.g., `beforeUpdateEvent`) trigger Alpine methods, which call `@this.call('methodName')`.

3.  **Data Sovereignty (Source of Truth):**
    - The Database is the ONLY source of truth.
    - The Calendar UI is a *projection* of the database state.
    - **Optimistic UI:** We visually update the calendar instantly on drag-drop, then send the request. If the request fails, we revert the visual change.

4.  **Timezone Discipline:**
    - **Database:** Always UTC (`datetime` columns).
    - **Transport:** ISO 8601 Strings (UTC).
    - **Display:** Converted to User's Timezone by TOAST UI (using the `timezone` config option).

5.  **Strict Typing:**
    - Use `AppointmentData` DTOs where possible.
    - Use `AppointmentType` Enum for color and category logic.

## 📂 Execution Steps

| File | Description | Complexity |
| :--- | :--- | :--- |
| **[01-frontend-setup.md](01-frontend-setup.md)** | NPM dependencies, robust JS Wrapper class, CSS Dark Mode overrides. | ⭐⭐ |
| **[02-calendar-component.md](02-calendar-component.md)** | The core Volt Component (`Calendar.php`) and Alpine logic. | ⭐⭐⭐⭐⭐ |
| **[03-agenda-view.md](03-agenda-view.md)** | Custom "Agenda" view implementation for Mobile/List preference. | ⭐⭐⭐ |
| **[04-backend-logic.md](04-backend-logic.md)** | Eloquent queries, Data Transformation, Performance optimization. | ⭐⭐⭐ |
| **[05-modals.md](05-modals.md)** | Interaction handling with Flux UI Modals (Create, Edit, Show). | ⭐⭐ |

## 📦 Dependencies

- **NPM:** `@toast-ui/calendar`
- **Composer:** `simshaun/recurr` (Reserved for future, Phase 5)

## 🎨 Design System Compliance
- **Colors:** Use `App\Enums\AppointmentType::color()` for event colors.
- **Dark Mode:** Override TOAST UI CSS variables to use `zinc-900`, `zinc-800` from Tailwind.
- **Components:** Control buttons (Next/Prev/Today) MUST use `flux:button`.
