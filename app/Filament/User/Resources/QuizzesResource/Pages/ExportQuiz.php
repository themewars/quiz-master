<?php

namespace App\Filament\User\Resources\QuizzesResource\Pages;

use App\Models\Quiz;
use App\Services\ExamExportService;
use Filament\Actions\Action;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Radio;
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
    public $includeAnswerKey = false;
    public $exportTemplate = 'standard';
    public $fontSize = 'medium';
    public $previewHtml = '';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Toggle::make('includeInstructions')
                    ->label('Include Instructions')
                    ->default(false)
                    ->live()
                    ->afterStateUpdated(fn () => $this->generatePreview()),
                
                Toggle::make('includeAnswerKey')
                    ->label('Include Answer Key')
                    ->default(false)
                    ->live()
                    ->afterStateUpdated(fn () => $this->generatePreview()),
                
                Select::make('exportTemplate')
                    ->label('Export Template')
                    ->options([
                        'standard' => 'Standard',
                        'academic' => 'Academic',
                        'professional' => 'Professional',
                        'minimal' => 'Minimal'
                    ])
                    ->default('standard')
                    ->live()
                    ->afterStateUpdated(fn () => $this->generatePreview()),
                
                Radio::make('fontSize')
                    ->label('Font Size')
                    ->options([
                        'small' => 'Small (10pt)',
                        'medium' => 'Medium (12pt)',
                        'large' => 'Large (14pt)'
                    ])
                    ->default('medium')
                    ->live()
                    ->afterStateUpdated(fn () => $this->generatePreview()),
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

    public function mount(): void
    {
        parent::mount();
        $this->generatePreview();
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

    public function generatePreview()
    {
        try {
            $exportService = new ExamExportService();
            
            $includeInstructions = $this->data['includeInstructions'] ?? false;
            $includeAnswerKey = $this->data['includeAnswerKey'] ?? false;
            $template = $this->data['exportTemplate'] ?? 'standard';
            $fontSize = $this->data['fontSize'] ?? 'medium';
            
            $this->previewHtml = $exportService->generatePreviewHtml(
                $this->record,
                $template,
                $includeInstructions,
                $includeAnswerKey,
                $fontSize
            );
        } catch (\Exception $e) {
            $this->previewHtml = '<div class="text-red-500">Preview generation failed: ' . $e->getMessage() . '</div>';
        }
    }

    private function exportExamPaper($format)
    {
        try {
            Log::info('Export started', ['quiz_id' => $this->record->id, 'format' => $format]);
            
            $exportService = new ExamExportService();
            
            // Get all export settings
            $includeInstructions = $this->data['includeInstructions'] ?? false;
            $includeAnswerKey = $this->data['includeAnswerKey'] ?? false;
            $template = $this->data['exportTemplate'] ?? 'standard';
            $fontSize = $this->data['fontSize'] ?? 'medium';
            
            $result = $exportService->exportExamPaper(
                $this->record,
                $format,
                $template,
                $includeInstructions,
                $includeAnswerKey,
                $fontSize
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