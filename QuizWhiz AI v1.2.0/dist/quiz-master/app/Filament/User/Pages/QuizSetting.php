<?php

namespace App\Filament\User\Pages;

use App\Models\User;
use Filament\Forms\Form;
use Filament\Pages\Page;
use App\Enums\UserSidebar;
use App\Models\UserSetting;
use Exception;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;

class QuizSetting extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.user.pages.quiz-setting';

    protected static ?int $navigationSort = UserSidebar::QUIZ_SETTINGS->value;

    public ?array $data = [];
    public static function getNavigationLabel(): string
    {
        return __('messages.setting.quiz_settings');
    }

    public function getTitle(): string
    {
        return __('messages.setting.quiz_settings');
    }

    public function mount()
    {
        $this->form->fill([
            'hide_participant_email_in_leaderboard' => getUserSettings('hide_participant_email_in_leaderboard') ?? 1,
            'preset_language' => getUserSettings('preset_language') ?? 'en',
            'preset_difficulty' => getUserSettings('preset_difficulty') ?? 0,
            'preset_question_type' => getUserSettings('preset_question_type') ?? 0,
            'preset_question_count' => getUserSettings('preset_question_count') ?? 10,
            'preset_default_tab' => getUserSettings('preset_default_tab') ?? \App\Models\Quiz::TEXT_TYPE,
        ]);
    }

    public function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label(__('filament-panels::resources/pages/edit-record.form.actions.save.label'))
                ->submit('save'),
        ];
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                ToggleButtons::make('hide_participant_email_in_leaderboard')
                    ->label(__('messages.setting.hide_participant_email_in_leaderboard') . ':')
                    ->options([
                        '1' => __('messages.setting.show'),
                        '0' => __('messages.setting.hide'),
                    ])
                    ->inline()
                    ->required(),
                Select::make('preset_language')
                    ->label(__('Default Language'))
                    ->options(getAllLanguages())
                    ->searchable(),
                Select::make('preset_difficulty')
                    ->label(__('Default Difficulty'))
                    ->options(\App\Models\Quiz::DIFF_LEVEL)
                    ->default(0),
                Select::make('preset_question_type')
                    ->label(__('Default Question Type'))
                    ->options(\App\Models\Quiz::QUIZ_TYPE)
                    ->default(0),
                TextInput::make('preset_question_count')
                    ->label(__('Default Number of Questions'))
                    ->numeric()
                    ->minValue(1)
                    ->default(10),
                Select::make('preset_default_tab')
                    ->label(__('Default Create Tab'))
                    ->options([
                        \App\Models\Quiz::TEXT_TYPE => 'Text',
                        \App\Models\Quiz::SUBJECT_TYPE => 'Subject',
                        \App\Models\Quiz::URL_TYPE => 'URL',
                        \App\Models\Quiz::UPLOAD_TYPE => 'Upload',
                        \App\Models\Quiz::IMAGE_TYPE => 'Image',
                    ])
                    ->default(\App\Models\Quiz::TEXT_TYPE),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        try {
            foreach ($data as $key => $value) {
                $setting = UserSetting::where('key', $key)
                    ->where('user_id', auth()->id())
                    ->first();
                if ($setting) {
                    $setting->update(['value' => $value]);
                } else {
                    UserSetting::create([
                        'user_id' => auth()->id(),
                        'key' => $key,
                        'value' => $value,
                    ]);
                }
            }

            Notification::make()
                ->success()
                ->title(__('messages.setting.quiz_settings_updated_success'))
                ->send();
        } catch (Exception $exception) {
            Notification::make()
                ->danger()
                ->title($exception->getMessage())
                ->send();
        }
    }
}
