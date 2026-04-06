<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group.
|
| Make building APIs great again!
|
*/

// Health check endpoint
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toIso8601String(),
        'version' => config('api-starter-kit.version', 'v1'),
    ]);
});

// Example authenticated routes
// Route::middleware('api.auth')->group(function () {
//     Route::get('/user', function (Request $request) {
//         return response()->json([
//             'data' => $request->user(),
//             'message' => 'User retrieved successfully',
//         ]);
//     });
//     
//     // Add your authenticated routes here
// });

// Public API routes
// Route::prefix('public')->group(function () {
//     // Add your public routes here
// });
