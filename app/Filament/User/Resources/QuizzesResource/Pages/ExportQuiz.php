<?php

namespace App\Filament\User\Resources\QuizzesResource\Pages;

use App\Models\Quiz;
use App\Services\ExamExportService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Radio;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;

class ExportQuiz extends Page
{
    protected static string $resource = \App\Filament\User\Resources\QuizzesResource::class;
    protected static string $view = 'filament.user.resources.quizzes-resource.pages.export-quiz';

    public Quiz $record;
    public $exportFormat = 'pdf';
    public $exportTemplate = 'standard';

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
            
            $result = $exportService->exportExamPaper(
                $this->record,
                $this->exportFormat,
                $this->exportTemplate
            );

            Notification::make()
                ->success()
                ->title('Exam Paper Exported Successfully!')
                ->body("Your exam paper has been exported as {$this->exportFormat} format.")
                ->actions([
                    \Filament\Notifications\Actions\Action::make('download')
                        ->label('Download')
                        ->url($result['download_url'])
                        ->openUrlInNewTab()
                ])
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title('Export Failed')
                ->body('There was an error exporting your exam paper. Please try again.')
                ->send();
        }
    }

    public function getTitle(): string
    {
        return 'Export Exam Paper: ' . $this->record->title;
    }
}
