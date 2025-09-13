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

// Quiz progress API routes
Route::middleware('web')->get('/quiz-progress', function (Request $request) {
    $user = auth()->user();
    if (!$user) {
        return response()->json(['quiz' => null]);
    }
    
    // Debug: Check all recent quizzes for this user
    $recentQuizzes = \App\Models\Quiz::where('user_id', $user->id)
        ->orderBy('created_at', 'desc')
        ->limit(3)
        ->get(['id', 'generation_status', 'generation_progress_done', 'generation_progress_total', 'question_count']);
    
    \Log::info("Recent quizzes for user {$user->id}:", $recentQuizzes->toArray());
    
    $quiz = \App\Models\Quiz::where('user_id', $user->id)
        ->where('generation_status', 'processing')
        ->orderBy('created_at', 'desc')
        ->first();
    
    if ($quiz) {
        \Log::info("Found processing quiz {$quiz->id} with status: {$quiz->generation_status}");
        return response()->json([
            'quiz' => [
                'id' => $quiz->id,
                'status' => $quiz->generation_status,
                'progress_done' => $quiz->generation_progress_done ?? 0,
                'progress_total' => $quiz->generation_progress_total ?? 0,
                'question_count' => $quiz->question_count ?? 0
            ]
        ]);
    }
    
    \Log::info("No processing quiz found for user {$user->id}");
    return response()->json(['quiz' => null]);
});

Route::middleware('web')->get('/quiz-status/{id}', function (Request $request, $id) {
    $user = auth()->user();
    if (!$user) {
        return response()->json(['quiz' => null]);
    }
    
    $quiz = \App\Models\Quiz::where('id', $id)
        ->where('user_id', $user->id)
        ->first();
    
    if ($quiz) {
        return response()->json([
            'quiz' => [
                'id' => $quiz->id,
                'status' => $quiz->generation_status,
                'progress_done' => $quiz->generation_progress_done ?? 0,
                'progress_total' => $quiz->generation_progress_total ?? 0,
                'question_count' => $quiz->question_count ?? 0
            ]
        ]);
    }
    
    return response()->json(['quiz' => null]);
});
