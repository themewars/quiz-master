<?php

namespace App\Models;

use App\Enums\PlanFrequency;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use HasFactory;

    protected $table = 'plans';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'description',
        'frequency',
        'no_of_exam',
        'price',
        'trial_days',
        'assign_default',
        'status',
        'currency_id',
        // New advanced plan fields
        'exams_per_month',
        'max_questions_per_exam',
        'max_questions_per_month',
        'pdf_export_enabled',
        'word_export_enabled',
        'youtube_quiz_enabled',
        'ppt_quiz_enabled',
        'answer_key_enabled',
        'white_label_enabled',
        'watermark_enabled',
        'priority_support_enabled',
        'multi_teacher_enabled',
        'allowed_question_types',
        'badge_text',
        'payment_gateway_plan_id',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'name' => 'string',
        'frequency' => 'integer',
        'no_of_exam' => 'integer',
        'price' => 'double',
        'trial_days' => 'integer',
        'assign_default' => 'boolean',
        'status' => 'boolean',
        // New advanced plan fields
        'exams_per_month' => 'integer',
        'max_questions_per_exam' => 'integer',
        'max_questions_per_month' => 'integer',
        'pdf_export_enabled' => 'boolean',
        'word_export_enabled' => 'boolean',
        'youtube_quiz_enabled' => 'boolean',
        'ppt_quiz_enabled' => 'boolean',
        'answer_key_enabled' => 'boolean',
        'white_label_enabled' => 'boolean',
        'watermark_enabled' => 'boolean',
        'priority_support_enabled' => 'boolean',
        'multi_teacher_enabled' => 'boolean',
        'allowed_question_types' => 'array',
    ];

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * Check if plan allows unlimited exams
     */
    public function hasUnlimitedExams(): bool
    {
        return $this->exams_per_month === -1;
    }

    /**
     * Check if plan allows unlimited questions per exam
     */
    public function hasUnlimitedQuestionsPerExam(): bool
    {
        return $this->max_questions_per_exam === -1;
    }

    /**
     * Check if plan allows unlimited questions per month
     */
    public function hasUnlimitedQuestionsPerMonth(): bool
    {
        return $this->max_questions_per_month === -1;
    }

    /**
     * Get allowed question types for this plan
     */
    public function getAllowedQuestionTypes(): array
    {
        return $this->allowed_question_types ?? ['mcq'];
    }

    /**
     * Check if plan allows specific question type
     */
    public function allowsQuestionType(string $type): bool
    {
        return in_array($type, $this->getAllowedQuestionTypes());
    }

    /**
     * Check if plan allows specific feature
     */
    public function allowsFeature(string $feature): bool
    {
        $featureMap = [
            'pdf_export' => 'pdf_export_enabled',
            'word_export' => 'word_export_enabled',
            'youtube_quiz' => 'youtube_quiz_enabled',
            'ppt_quiz' => 'ppt_quiz_enabled',
            'answer_key' => 'answer_key_enabled',
            'white_label' => 'white_label_enabled',
            'watermark' => 'watermark_enabled',
            'priority_support' => 'priority_support_enabled',
            'multi_teacher' => 'multi_teacher_enabled',
        ];

        $field = $featureMap[$feature] ?? null;
        return $field ? $this->$field : false;
    }

    public static function getForm()
    {
        return [
            Section::make()->schema([
                TextInput::make('name')
                    ->label(__('messages.common.name') . ':')
                    ->placeholder(__('messages.common.name'))
                    ->validationAttribute(__('messages.common.name'))
                    ->required()
                    ->maxLength(255),
                ToggleButtons::make('frequency')
                    ->label(__('messages.plan.frequency') . ':')
                    ->validationAttribute(__('messages.plan.frequency'))
                    ->inline()
                    ->default(PlanFrequency::MONTHLY)
                    ->options(PlanFrequency::class)
                    ->required(),
                Textarea::make('description')
                    ->label(__('messages.quiz.description') . ':')
                    ->placeholder(__('messages.quiz.description'))
                    ->validationAttribute(__('messages.quiz.description'))
                    ->required(),
                TextInput::make('trial_days')
                    ->label(__('messages.plan.trial_days') . ':')
                    ->placeholder(__('messages.plan.trial_days'))
                    ->validationAttribute(__('messages.plan.trial_days'))
                    ->numeric()
                    ->integer(),
                TextInput::make('no_of_exam')
                    ->numeric()
                    ->label(__('messages.plan.no_of_quizzes') . ':')
                    ->placeholder(__('messages.plan.no_of_quizzes'))
                    ->validationAttribute(__('messages.plan.no_of_quizzes'))
                    ->minValue(1)
                    ->required(),
                TextInput::make('exams_per_month')
                    ->numeric()
                    ->label('Exams per Month (-1 for unlimited):')
                    ->placeholder('Exams per month')
                    ->default(3)
                    ->helperText('Number of exams allowed per month. Use -1 for unlimited.'),
                TextInput::make('max_questions_per_exam')
                    ->numeric()
                    ->label('Max Questions per Exam:')
                    ->placeholder('Max questions per exam')
                    ->default(10)
                    ->minValue(1)
                    ->required(),
                TextInput::make('max_questions_per_month')
                    ->numeric()
                    ->label('Max Questions per Month (optional):')
                    ->placeholder('Max questions per month')
                    ->helperText('Leave empty for no monthly question limit.'),
                Select::make('currency_id')
                    ->label(__('messages.currency.currency') . ':')
                    ->placeholder(__('messages.currency.currency'))
                    ->validationAttribute(__('messages.currency.currency'))
                    ->options(function () {
                        return Currency::get()->mapWithKeys(function ($currency) {
                            return [$currency->id => $currency->symbol . ' - ' . $currency->name];
                        })->toArray();
                    })
                    ->live()
                    ->searchable()
                    ->required()
                    ->preload(),
                TextInput::make('price')
                    ->label(__('messages.common.price') . ':')
                    ->placeholder(__('messages.common.price'))
                    ->validationAttribute(__('messages.common.price'))
                    ->numeric()
                    ->default(0)
                    ->required()
                    ->prefix(function (Get $get) {
                        return $get('currency_id') ? Currency::find($get('currency_id'))->symbol : '$';
                    }),
                Group::make([
                    Toggle::make('assign_default')
                        ->label(__('messages.plan.assign_default'))
                        ->live()
                        ->afterStateUpdated(function (Set $set, $state, string $operation, ?Model $record) {
                            $default = Plan::where('assign_default', true)->exists();
                            if ($operation === 'edit') {
                                $default = Plan::where('assign_default', true)->where('id', '!=', $record->id)->exists();
                            }
                            if (!$default && !$state) {
                                $set('assign_default', true);
                                Notification::make()
                                    ->title(__('messages.plan.default_plan_cannot_turned_off'))
                                    ->danger()
                                    ->send();
                            }
                        }),
                ])->columns(2),
            ])->columns(2),
            
            // Feature Toggles Section
            Section::make('Plan Features')
                ->schema([
                    Group::make([
                        Toggle::make('pdf_export_enabled')
                            ->label('PDF Export')
                            ->default(false),
                        Toggle::make('word_export_enabled')
                            ->label('Word Export')
                            ->default(false),
                        Toggle::make('youtube_quiz_enabled')
                            ->label('YouTube → Quiz')
                            ->default(false),
                        Toggle::make('ppt_quiz_enabled')
                            ->label('PPT → Quiz')
                            ->default(false),
                        Toggle::make('answer_key_enabled')
                            ->label('Answer Key Generation')
                            ->default(false),
                        Toggle::make('white_label_enabled')
                            ->label('White-label Branding')
                            ->default(false),
                    ])->columns(3),
                    Group::make([
                        Toggle::make('watermark_enabled')
                            ->label('Watermark on Exports')
                            ->default(true),
                        Toggle::make('priority_support_enabled')
                            ->label('Priority Support')
                            ->default(false),
                        Toggle::make('multi_teacher_enabled')
                            ->label('Multi-teacher Accounts')
                            ->default(false),
                    ])->columns(3),
                ]),
            
            // Question Types Section
            Section::make('Allowed Question Types')
                ->schema([
                    Select::make('allowed_question_types')
                        ->label('Question Types')
                        ->multiple()
                        ->options([
                            'mcq' => 'Multiple Choice Questions',
                            'short_answer' => 'Short Answer',
                            'long_answer' => 'Long Answer',
                            'true_false' => 'True/False',
                            'fill_blank' => 'Fill in the Blank',
                        ])
                        ->default(['mcq'])
                        ->required(),
                ]),
            
            // Plan Metadata Section
            Section::make('Plan Metadata')
                ->schema([
                    TextInput::make('badge_text')
                        ->label('Badge Text (e.g., "Recommended")')
                        ->placeholder('Plan badge text'),
                    TextInput::make('payment_gateway_plan_id')
                        ->label('Payment Gateway Plan ID')
                        ->placeholder('Razorpay/Stripe plan ID')
                        ->helperText('External payment gateway plan identifier'),
                ])->columns(2),
            ])->columns(2)
        ];
    }
}
