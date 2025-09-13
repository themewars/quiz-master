<?php

namespace App\Filament\User\Resources\QuizzesResource\Pages;

use App\Models\Quiz;
use App\Services\ExamExportService;
use App\Services\PlanValidationService;
use Filament\Actions\Action;
use Filament\Resources\Pages\Page;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class ExportQuiz extends Page
{
    protected static string $resource = \App\Filament\User\Resources\QuizzesResource::class;
    protected static string $view = 'filament.user.resources.quizzes-resource.pages.export-quiz';

    public Quiz $record;
    public $includeAnswerKey = false;
    public $includeInstructions = true;
    public $fontSize = 'medium';
    public $template = 'standard';
    public $pageSize = 'A4';
    public $orientation = 'portrait';
    public $compactMode = false;
    public $includeStudentInfo = true;
    public $includeTimestamp = true;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Back to Exam')
                ->url(fn() => $this->getResource()::getUrl('view', ['record' => $this->record]))
                ->color('gray'),
        ];
    }

    public function exportPDF()
    {
        $planCheck = app(PlanValidationService::class)->canUseFeature('pdf_export');
        if (!$planCheck['allowed']) {
            Notification::make()
                ->danger()
                ->title('Feature Not Available')
                ->body($planCheck['message'])
                ->send();
            return;
        }
        $this->exportExamPaper('pdf');
    }

    public function exportWord()
    {
        $planCheck = app(PlanValidationService::class)->canUseFeature('word_export');
        if (!$planCheck['allowed']) {
            Notification::make()
                ->danger()
                ->title('Feature Not Available')
                ->body($planCheck['message'])
                ->send();
            return;
        }
        $this->exportExamPaper('word');
    }

    public function exportHTML()
    {
        $this->exportExamPaper('html');
    }

    public function exportCompactPDF()
    {
        $this->compactMode = true;
        $this->fontSize = 'small';
        $this->includeInstructions = false;
        $this->exportExamPaper('pdf');
    }

    public function exportLargePDF()
    {
        $this->fontSize = 'large';
        $this->exportExamPaper('pdf');
    }

    public function exportLandscapePDF()
    {
        $this->orientation = 'landscape';
        $this->exportExamPaper('pdf');
    }

    public function exportA3PDF()
    {
        $this->pageSize = 'A3';
        $this->exportExamPaper('pdf');
    }

    private function exportExamPaper($format)
    {
        try {
            // Check answer key feature if answer key is included
            if ($this->includeAnswerKey) {
                $planCheck = app(PlanValidationService::class)->canUseFeature('answer_key');
                if (!$planCheck['allowed']) {
                    Notification::make()
                        ->danger()
                        ->title('Feature Not Available')
                        ->body($planCheck['message'])
                        ->send();
                    return;
                }
            }

            Log::info('Export started', ['quiz_id' => $this->record->id, 'format' => $format]);
            
            $exportService = new ExamExportService();
            
            $result = $exportService->exportExamPaper(
                $this->record,
                $format,
                $this->template,
                $this->includeInstructions,
                $this->includeAnswerKey,
                $this->fontSize,
                $this->pageSize,
                $this->orientation,
                $this->compactMode,
                $this->includeStudentInfo,
                $this->includeTimestamp
            );

            Notification::make()
                ->success()
                ->title('Exam Paper Exported Successfully!')
                ->body('Your exam paper has been exported as ' . strtoupper($format) . ' format.')
                ->actions([
                    \Filament\Notifications\Actions\Action::make('download')
                        ->label('Download')
                        ->url($result['download_url'])
                        ->openUrlInNewTab()
                ])
                ->send();

        } catch (\Exception $e) {
            Log::error('Export failed', [
                'quiz_id' => $this->record->id,
                'format' => $format,
                'message' => $e->getMessage(),
            ]);
            
            Notification::make()
                ->danger()
                ->title('Export Failed')
                ->body('There was an error exporting your exam paper. ' . $e->getMessage())
                ->send();
        }
    }

    public function getTitle(): string
    {
        return 'Export Exam Paper: ' . $this->record->title;
    }
}