<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| The legacy `/api/shifts` and `/api/shifts/bulk` routes are gone. They took a `user_id`
| from the query string with no authentication and no team scoping, so anyone could read or
| overwrite anyone else's worked hours. Their replacements are `shifts.index` /
| `shifts.store` in routes/web.php, inside the `tenant` group, and they only ever act on
| the logged-in user unless an admin asks for someone else.
|
| resources/js/app.js still calls the old paths — see _planning/21 §4.
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
