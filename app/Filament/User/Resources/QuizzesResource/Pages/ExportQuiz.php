<?php

namespace App\Filament\User\Resources\QuizzesResource\Pages;

use App\Models\Quiz;
use App\Services\ExamExportService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Resources\Pages\Page;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class ExportQuiz extends Page
{
    protected static string $resource = \App\Filament\User\Resources\QuizzesResource::class;
    protected static string $view = 'filament.user.resources.quizzes-resource.pages.export-quiz';

    public Quiz $record;
    public $exportFormat = 'pdf';

    public function mount(): void
    {
        parent::mount();
    }

    public function updatedExportFormat($value): void
    {
        $this->exportFormat = $value;
        Log::info('Format updated:', ['new_format' => $value]);
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
            Action::make('export')
                ->label('Export Exam Paper (PDF)')
                ->color('success')
                ->icon('heroicon-o-document-arrow-down')
                ->action('exportExamPaper'),
        ];
    }

    public function exportExamPaper()
    {
        try {
            Log::info('Export started', ['quiz_id' => $this->record->id]);
            
            $exportService = new ExamExportService();
            
            // For now, use property value
            $selectedFormat = $this->exportFormat ?? 'pdf';
            
            Log::info('Format selection debug:', [
                'this->exportFormat' => $this->exportFormat ?? 'empty',
                'selectedFormat' => $selectedFormat,
            ]);
            
            $result = $exportService->exportExamPaper(
                $this->record,
                $selectedFormat,
                'standard',
                false // Don't include instructions for now
            );

            Notification::make()
                ->success()
                ->title('Exam Paper Exported Successfully!')
                ->body('Your exam paper has been exported as ' . strtoupper($selectedFormat) . ' format.')
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