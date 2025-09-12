<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Quiz progress API for live updates
Route::middleware('auth:sanctum')->get('/quiz-progress', function (Request $request) {
    $quiz = \App\Models\Quiz::where('user_id', $request->user()->id)
        ->where('generation_status', 'processing')
        ->latest()
        ->first();
        
    if ($quiz) {
        return response()->json([
            'quiz' => [
                'id' => $quiz->id,
                'progress_done' => $quiz->generation_progress_done ?? 0,
                'progress_total' => $quiz->generation_progress_total ?? 0,
                'status' => $quiz->generation_status,
            ]
        ]);
    }
    
    return response()->json(['quiz' => null]);
});
