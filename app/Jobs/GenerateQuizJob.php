<?php

namespace App\Jobs;

use App\Models\Quiz;
use App\Models\Question;
use App\Models\Answer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GenerateQuizJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $quizId;
    public string $model;
    public string $prompt;
    public int $totalQuestions;
    public int $batchSize;

    public function __construct(int $quizId, string $model, string $prompt, int $totalQuestions, int $batchSize = 10)
    {
        $this->quizId = $quizId;
        $this->model = $model;
        $this->prompt = $prompt;
        $this->totalQuestions = $totalQuestions;
        $this->batchSize = $batchSize;
    }

    public function handle(): void
    {
        Log::info("Starting GenerateQuizJob for quiz {$this->quizId}");
        
        $quiz = Quiz::find($this->quizId);
        if (!$quiz) {
            Log::error("Quiz {$this->quizId} not found");
            return;
        }

        // Update quiz status to processing
        $quiz->update([
            'generation_status' => 'processing',
            'generation_progress_total' => $this->totalQuestions,
            'generation_progress_done' => 0,
        ]);

        Log::info("Quiz {$this->quizId} after update - Progress total: {$quiz->generation_progress_total}, Progress done: {$quiz->generation_progress_done}");

        $remaining = $this->totalQuestions - ($quiz->generation_progress_done ?? 0);
        while ($remaining > 0) {
            $take = min($this->batchSize, $remaining);
            try {
                $batchPrompt = $this->prompt . "\n\nYou MUST return exactly {$take} questions in this response.";

                $apiKey = \App\Models\Setting::first()?->openai_api_key ?? '';
                if (empty($apiKey)) {
                    throw new \RuntimeException('OpenAI API key not configured');
                }
                
                Log::info("Making OpenAI API call for {$take} questions. Model: {$this->model}");
                
                $response = Http::withHeaders([
                        'Authorization' => 'Bearer ' . $apiKey,
                        'Content-Type' => 'application/json',
                    ])
                    ->timeout(120)
                    ->retry(2, 1500)
                    ->post('https://api.openai.com/v1/chat/completions', [
                        'model' => $this->model,
                        'messages' => [
                            ['role' => 'user', 'content' => $batchPrompt],
                        ],
                    ]);
                
                Log::info("OpenAI API response status: " . $response->status());

                if ($response->failed()) {
                    throw new \RuntimeException($response->json()['error']['message'] ?? 'OpenAI error');
                }

                $content = $response['choices'][0]['message']['content'] ?? '';
                Log::info("Received content length: " . strlen($content));

                if (empty($content)) {
                    throw new \RuntimeException('Empty response from OpenAI');
                }

                // Clean and parse JSON
                $quizData = trim($content);
                if (stripos($quizData, '```json') === 0) {
                    $quizData = preg_replace('/^```json\s*|\s*```$/', '', $quizData);
                    $quizData = trim($quizData);
                }

                $questions = json_decode($quizData, true);
                if (!is_array($questions) || empty($questions)) {
                    throw new \RuntimeException('Invalid JSON response from OpenAI');
                }

                Log::info("Parsed {$take} questions from API response");

                // Create questions and answers
                $createdCount = 0;
                foreach ($questions as $questionData) {
                    if (!isset($questionData['question'], $questionData['answers'])) {
                        Log::warning("Skipping invalid question structure");
                        continue;
                    }

                    $question = Question::create([
                        'quiz_id' => $this->quizId,
                        'title' => $questionData['question'],
                    ]);

                    foreach ($questionData['answers'] as $answerData) {
                        $isCorrect = false;
                        $correctKey = $questionData['correct_answer_key'] ?? '';

                        if (is_array($correctKey)) {
                            $isCorrect = in_array($answerData['title'], $correctKey);
                        } else {
                            $isCorrect = $answerData['title'] === $correctKey;
                        }

                        Answer::create([
                            'question_id' => $question->id,
                            'title' => $answerData['title'],
                            'is_correct' => $isCorrect,
                        ]);
                    }
                    $createdCount++;
                }

                // Update progress
                $currentProgress = $quiz->generation_progress_done ?? 0;
                $newProgress = $currentProgress + $createdCount;
                
                $quiz->update([
                    'generation_progress_done' => $newProgress,
                ]);

                Log::info("Created {$createdCount} questions. Total progress: {$newProgress}/{$this->totalQuestions}");

                $remaining = $this->totalQuestions - $newProgress;

            } catch (\Exception $e) {
                Log::error("Error in GenerateQuizJob: " . $e->getMessage());
                
                $quiz->update([
                    'generation_status' => 'failed',
                    'generation_error' => $e->getMessage(),
                ]);
                
                throw $e;
            }
        }

        // Mark as completed
        $quiz->update([
            'generation_status' => 'completed',
        ]);

        Log::info("GenerateQuizJob completed for quiz {$this->quizId}");
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("GenerateQuizJob failed for quiz {$this->quizId}: " . $exception->getMessage());
        
        $quiz = Quiz::find($this->quizId);
        if ($quiz) {
            $quiz->update([
                'generation_status' => 'failed',
                'generation_error' => $exception->getMessage(),
            ]);
        }
    }
}