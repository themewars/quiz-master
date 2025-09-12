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
        $quiz = Quiz::find($this->quizId);
        if (!$quiz) {
            return;
        }

        $quiz->update([
            'generation_status' => 'processing',
            'generation_progress_total' => $this->totalQuestions,
        ]);

        $remaining = $this->totalQuestions - ($quiz->generation_progress_done ?? 0);
        while ($remaining > 0) {
            $take = min($this->batchSize, $remaining);
            try {
                $batchPrompt = $this->prompt . "\n\nYou MUST return exactly {$take} questions in this response.";

                // Use the same key naming as CreateQuizzes (open_api_key) with config fallback
                $apiKey = getSetting()->open_api_key ?: (config('services.open_ai.open_api_key') ?? '');

                $response = Http::withHeaders([
                        'Authorization' => 'Bearer ' . $apiKey,
                        'Content-Type' => 'application/json',
                    ])
                    ->timeout(300)
                    ->retry(3, 2000)
                    ->post('https://api.openai.com/v1/chat/completions', [
                        'model' => $this->model,
                        'messages' => [
                            ['role' => 'user', 'content' => $batchPrompt],
                        ],
                    ]);

                if ($response->failed()) {
                    $body = $response->json();
                    $message = is_array($body) ? ($body['error']['message'] ?? json_encode($body)) : 'OpenAI error';
                    throw new \RuntimeException($message);
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
                $remaining -= $created;
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


