<?php

namespace App\Jobs;

use App\Models\Answer;
use App\Models\Question;
use App\Models\Quiz;
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

    public int $timeout = 600; // job-level timeout

    public function __construct(
        public int $quizId,
        public string $model,
        public string $prompt,
        public int $totalQuestions,
        public int $batchSize = 10
    ) {}

    public function handle(): void
    {
        Log::info("Starting GenerateQuizJob for quiz {$this->quizId} with {$this->totalQuestions} questions");
        
        // Wait a moment to ensure quiz is committed to database
        sleep(2);
        
        $quiz = Quiz::find($this->quizId);
        if (!$quiz) {
            Log::error("Quiz {$this->quizId} not found after waiting");
            return;
        }
        
        Log::info("Found quiz {$this->quizId}, proceeding with generation");

        $quiz->update([
            'generation_status' => 'processing',
            'generation_progress_total' => $this->totalQuestions,
            'generation_progress_done' => 0,
        ]);
        
        Log::info("Quiz {$this->quizId} status updated to processing. Progress total: {$this->totalQuestions}");
        
        // Verify the update worked
        $quiz->refresh();
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
                if (stripos($content, '```json') === 0) {
                    $content = preg_replace('/^```json\s*|\s*```$/', '', $content);
                    $content = trim($content);
                }
                $questions = json_decode($content, true);
                if (!is_array($questions) || empty($questions)) {
                    throw new \RuntimeException('Empty or invalid questions JSON');
                }

                $created = 0;
                foreach ($questions as $q) {
                    if (!isset($q['question'], $q['answers']) || !is_array($q['answers'])) {
                        continue;
                    }
                    $question = Question::create([
                        'quiz_id' => $quiz->id,
                        'title' => $q['question'],
                    ]);
                    $correctKey = $q['correct_answer_key'] ?? null;
                    foreach ($q['answers'] as $ans) {
                        $isCorrect = false;
                        if (is_array($correctKey)) {
                            $isCorrect = in_array($ans['title'] ?? '', $correctKey);
                        } else {
                            $isCorrect = ($ans['title'] ?? '') === $correctKey;
                        }
                        Answer::create([
                            'question_id' => $question->id,
                            'title' => $ans['title'] ?? '',
                            'is_correct' => $isCorrect,
                        ]);
                    }
                    $created++;
                }

                $quiz->increment('generation_progress_done', $created);
                $quiz->increment('question_count', $created);
                $remaining -= $created;
                
                Log::info("Generated {$created} questions for quiz {$quiz->id}. Progress: {$quiz->generation_progress_done}/{$quiz->generation_progress_total}");
            } catch (\Throwable $e) {
                Log::error('GenerateQuizJob failed: ' . $e->getMessage());
                $quiz->update([
                    'generation_status' => 'failed',
                    'generation_error' => $e->getMessage(),
                ]);
                return;
            }
        }

        $quiz->update(['generation_status' => 'completed']);
    }
}


