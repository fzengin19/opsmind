<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| API versioning: /api/v1/...
|
*/

// V1 Routes
Route::prefix('v1')->group(function () {
    require base_path('routes/api/v1/appointments.php');
    require base_path('routes/api/v1/contacts.php');
    require base_path('routes/api/v1/tasks.php');
});
