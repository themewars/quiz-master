<?php

namespace App\Filament\User\Resources\QuizzesResource\Pages;

use App\Models\Quiz;
use App\Services\ExamExportService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;

class ExportQuiz extends Page
{
    protected static string $resource = \App\Filament\User\Resources\QuizzesResource::class;
    protected static string $view = 'filament.user.resources.quizzes-resource.pages.export-quiz';

    public Quiz $record;

    public function updatedData($property, $value): void
    {
        // Trigger preview update when any form field changes
        if (in_array($property, ['includeInstructions', 'exportFormat', 'exportTemplate'])) {
            $this->dispatch('$refresh');
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Back to Quiz')
                ->url(fn() => $this->getResource()::getUrl('view', ['record' => $this->record]))
                ->color('gray'),
        ];
    }

    public function form(Form $form): Form
    {
        $exportService = new ExamExportService();
        
        return $form
            ->schema([
                Select::make('exportFormat')
                    ->label('Export Format')
                    ->options($exportService->getAvailableFormats())
                    ->default('pdf')
                    ->required()
                    ->live(),
                
                Select::make('exportTemplate')
                    ->label('Template Style')
                    ->options($exportService->getAvailableTemplates())
                    ->default('standard')
                    ->required(),
                
                Radio::make('includeAnswerKey')
                    ->label('Include Answer Key')
                    ->boolean()
                    ->default(true)
                    ->helperText('Include answer key in the exported document'),

                Toggle::make('includeInstructions')
                    ->label('Include Instructions')
                    ->default(false)
                    ->live(),
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('export')
                ->label('Export Exam Paper')
                ->color('success')
                ->icon('heroicon-o-document-arrow-down')
                ->action('exportExamPaper'),
        ];
    }

    public function exportExamPaper()
    {
        try {
            $exportService = new ExamExportService();
            
            // Get form data using Filament's method
            $formData = $this->form->getState();
            
            // Debug: Log the form data
            Log::info('Export form data:', [
                'formData' => $formData,
                'exportFormat' => $formData['exportFormat'] ?? 'pdf',
                'exportTemplate' => $formData['exportTemplate'] ?? 'standard',
                'includeInstructions' => $formData['includeInstructions'] ?? false,
            ]);
            
            $result = $exportService->exportExamPaper(
                $this->record,
                $formData['exportFormat'] ?? 'pdf',
                $formData['exportTemplate'] ?? 'standard',
                (bool) ($formData['includeInstructions'] ?? false)
            );

            Notification::make()
                ->success()
                ->title('Exam Paper Exported Successfully!')
                ->body("Your exam paper has been exported as " . ($formData['exportFormat'] ?? 'pdf') . " format.")
                ->actions([
                    \Filament\Notifications\Actions\Action::make('download')
                        ->label('Download')
                        ->url($result['download_url'])
                        ->openUrlInNewTab()
                ])
                ->send();

        } catch (\Exception $e) {
            Log::error('Export exam failed', [
                'quiz_id' => $this->record->id ?? null,
                'format' => $formData['exportFormat'] ?? null,
                'template' => $formData['exportTemplate'] ?? null,
                'message' => $e->getMessage(),
            ]);
            Notification::make()
                ->danger()
                ->title('Export Failed')
                ->body('There was an error exporting your exam paper. '.(config('app.debug') ? $e->getMessage() : 'Please try again.'))
                ->send();
        }
    }

    /**
     * Live preview HTML of the exam paper (used in the page preview panel)
     */
    public function getPreviewHtmlProperty(): string
    {
        try {
            $quiz = $this->record->load(['questions.answers']);

            $questions = $quiz->questions;
            $totalQuestions = $questions->count();
            $examDate = now()->format('d/m/Y');
            $timeLimit = $quiz->time_configuration
                ? ($quiz->time . ' ' . ($quiz->time_type == 1 ? 'minutes per question' : 'minutes total'))
                : 'No time limit';

            $answerKey = [];
            foreach ($questions as $index => $question) {
                $correctAnswers = $question->answers()->where('is_correct', true)->get();
                $correctOptions = [];
                foreach ($correctAnswers as $answer) {
                    $answerIndex = $question->answers->search(function ($item) use ($answer) {
                        return $item->id === $answer->id;
                    });
                    if ($answerIndex !== false) {
                        $correctOptions[] = chr(65 + $answerIndex);
                    }
                }
                $answerKey[$index + 1] = implode(', ', $correctOptions);
            }

            return view('exports.exam-paper-html', [
                'quiz' => $quiz,
                'questions' => $questions,
                'totalQuestions' => $totalQuestions,
                'examDate' => $examDate,
                'timeLimit' => $timeLimit,
                'answerKey' => $answerKey,
                'template' => $this->form->getState()['exportTemplate'] ?? 'standard',
                'includeInstructions' => (bool) ($this->form->getState()['includeInstructions'] ?? false),
            ])->render();
        } catch (\Throwable $e) {
            Log::error('Export preview failed', [
                'quiz_id' => $this->record->id ?? null,
                'message' => $e->getMessage(),
            ]);
            return '<div style="color:#b91c1c">Preview unavailable.</div>';
        }
    }

    public function getTitle(): string
    {
        return 'Export Exam Paper: ' . $this->record->title;
    }
}
