# Step 4: Backend Data Logic & Routing

## 💾 1. The `Appointment` Model

Ensure the `Appointment` model has the necessary casts and relationships.

**Relationships:**
- `company()`: BelongsTo
- `attendees()`: HasMany
- `createdBy()`: BelongsTo (User)

## 📍 2. Routing (Web.php)

We need to register the Volt component in `routes/web.php` to make it accessible.

**File:** `routes/web.php`

```php
Route::middleware(['auth', 'ensure.has.company'])->group(function () {
    // ... existing routes
    
    // Calendar Route
    Volt::route('calendar', 'calendar.index')->name('calendar.index');
});
```

## 🌍 3. Timezone Handling (The Golden Rule)

1.  **Input:** User selects `10:00 AM` in `Europe/Istanbul`.
2.  **Transport:** Frontend sends `2023-10-01T07:00:00Z` (UTC equivalent).
3.  **Storage:** Database stores `2023-10-01 07:00:00`.
4.  **Fetch:** Controller reads `2023-10-01 07:00:00`.
5.  **Access:** `TOAST UI` config is set to `Europe/Istanbul`.
6.  **Display:** TOAST UI receives `7:00` UTC, converts to `10:00` Display time.

## 🔄 4. Update & Reschedule Logic

When an event is dragged in TOAST UI:

```php
public function updateAppointmentDate($id, $startIso, $endIso)
{
    $appt = Appointment::find($id);
    
    // Authorization
    $this->authorize('update', $appt);

    // Parse logic (Assuming ISO strings are UTC)
    $start = Carbon::parse($startIso)->setTimezone('UTC');
    $end = Carbon::parse($endIso)->setTimezone('UTC');

    $appt->update([
        'start_at' => $start,
        'end_at' => $end
    ]);
}
```

## ✅ Checklist
- [ ] Confirm Database uses UTC.
- [ ] Implement `EventObject` transformation.
- [ ] Add backend validation for start < end.
- [ ] Register Route in `web.php`.
