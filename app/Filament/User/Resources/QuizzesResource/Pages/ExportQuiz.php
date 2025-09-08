<?php

namespace App\Filament\User\Resources\QuizzesResource\Pages;

use App\Models\Quiz;
use App\Services\ExamExportService;
use Filament\Actions\Action;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Pages\Page;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class ExportQuiz extends Page
{
    protected static string $resource = \App\Filament\User\Resources\QuizzesResource::class;
    protected static string $view = 'filament.user.resources.quizzes-resource.pages.export-quiz';

    public Quiz $record;
    public $includeInstructions = false;

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Toggle::make('includeInstructions')
                    ->label('Include Instructions')
                    ->default(false)
                    ->live(),
            ])
            ->statePath('data');
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

    protected function getFormActions(): array
    {
        return [
            Action::make('export-pdf')
                ->label('Export PDF')
                ->color('success')
                ->icon('heroicon-o-document-arrow-down')
                ->action('exportPDF'),
            
            Action::make('export-word')
                ->label('Export Word')
                ->color('primary')
                ->icon('heroicon-o-document-text')
                ->action('exportWord'),
            
            Action::make('export-html')
                ->label('Export HTML')
                ->color('warning')
                ->icon('heroicon-o-code-bracket')
                ->action('exportHTML'),
        ];
    }

    public function exportPDF()
    {
        $this->exportExamPaper('pdf');
    }

    public function exportWord()
    {
        $this->exportExamPaper('word');
    }

    public function exportHTML()
    {
        $this->exportExamPaper('html');
    }

    private function exportExamPaper($format)
    {
        try {
            Log::info('Export started', ['quiz_id' => $this->record->id, 'format' => $format]);
            
            $exportService = new ExamExportService();
            
            // Get include instructions setting
            $includeInstructions = $this->data['includeInstructions'] ?? false;
            
            $result = $exportService->exportExamPaper(
                $this->record,
                $format,
                'standard',
                $includeInstructions
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