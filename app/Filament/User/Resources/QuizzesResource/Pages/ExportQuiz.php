<?php

namespace App\Filament\User\Resources\QuizzesResource\Pages;

use App\Models\Quiz;
use App\Services\ExamExportService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;

class ExportQuiz extends Page
{
    protected static string $resource = \App\Filament\User\Resources\QuizzesResource::class;
    protected static string $view = 'filament.user.resources.quizzes-resource.pages.export-quiz';

    public Quiz $record;

    public function mount(): void
    {
        parent::mount();
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
        return $form->schema([]);
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
            Log::info('Export button clicked', ['quiz_id' => $this->record->id]);
            
            Notification::make()
                ->success()
                ->title('Export Test')
                ->body('Export button is working! Quiz ID: ' . $this->record->id)
                ->send();

        } catch (\Exception $e) {
            Log::error('Export test failed', [
                'quiz_id' => $this->record->id ?? null,
                'message' => $e->getMessage(),
            ]);
            Notification::make()
                ->danger()
                ->title('Export Test Failed')
                ->body('Error: ' . $e->getMessage())
                ->send();
        }
    }

    public function getPreviewHtmlProperty(): string
    {
        return '<div class="text-gray-500">Preview will be available after export functionality is working.</div>';
    }

    public function getTitle(): string
    {
        return 'Export Exam Paper: ' . $this->record->title;
    }
}
