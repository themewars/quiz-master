<?php

namespace App\Filament\User\Resources\QuizzesResource\Pages;

use App\Models\Quiz;
use Filament\Actions\Action;
use Filament\Resources\Pages\Page;
use Filament\Notifications\Notification;

class ExportQuiz extends Page
{
    protected static string $resource = \App\Filament\User\Resources\QuizzesResource::class;
    protected static string $view = 'filament.user.resources.quizzes-resource.pages.export-quiz';

    public Quiz $record;

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
            Notification::make()
                ->success()
                ->title('Export Test')
                ->body('Export button is working! Quiz ID: ' . $this->record->id)
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title('Export Test Failed')
                ->body('Error: ' . $e->getMessage())
                ->send();
        }
    }

    public function getTitle(): string
    {
        return 'Export Exam Paper: ' . $this->record->title;
    }
}