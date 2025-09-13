<?php

namespace App\Filament\User\Widgets;

use Filament\Widgets\Widget;

class ExamCreationProgress extends Widget
{
    protected static string $view = 'filament.user.widgets.exam-creation-progress';

    protected int | string | array $columnSpan = 'full';

    public $isCreating = false;
    public $progressMessage = '';
    public $questionsGenerated = 0;
    public $totalQuestions = 0;
    public $currentStep = '';

    protected $listeners = [
        'exam-creation-started' => 'startProgress',
        'exam-progress-updated' => 'updateProgress', 
        'exam-creation-completed' => 'completeProgress',
        'exam-creation-error' => 'errorProgress',
        'update-progress-widget' => 'updateWidgetProperties',
    ];

    public function startProgress()
    {
        $this->isCreating = true;
        $this->progressMessage = 'Starting exam creation...';
        $this->questionsGenerated = 0;
        $this->currentStep = 'Preparing exam data...';
    }

    public function updateProgress()
    {
        // Progress will be updated via Livewire properties
    }

    public function completeProgress($data = [])
    {
        $this->isCreating = false;
        $this->currentStep = 'Exam created successfully!';
        $this->progressMessage = "All {$this->totalQuestions} questions completed!";
        
        // Redirect to edit page after 2 seconds
        $this->js('setTimeout(() => { window.location.href = "' . route('filament.user.resources.quizzes.edit', ['record' => $data['quizId'] ?? '']) . '"; }, 2000);');
    }

    public function errorProgress()
    {
        $this->isCreating = false;
        $this->currentStep = 'Error occurred';
        $this->progressMessage = 'Failed to create exam';
    }

    public function updateWidgetProperties($data)
    {
        $this->isCreating = $data['isCreating'] ?? false;
        $this->totalQuestions = $data['totalQuestions'] ?? 0;
        $this->questionsGenerated = $data['questionsGenerated'] ?? 0;
        $this->currentStep = $data['currentStep'] ?? '';
        $this->progressMessage = $data['progressMessage'] ?? '';
    }

    public function getProgressPercentage(): int
    {
        if ($this->totalQuestions === 0) {
            return 0;
        }
        
        return min(100, round(($this->questionsGenerated / $this->totalQuestions) * 100));
    }
}
