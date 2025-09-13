<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $quiz->title }} - Exam Paper</title>
    <style>
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
        }
        
        .title {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .exam-info {
            font-size: 14px;
            color: #666;
        }
        
        .instructions {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 30px;
        }
        
        .instructions h3 {
            margin-top: 0;
            color: #495057;
        }
        
        .instructions ul {
            margin: 10px 0;
            padding-left: 20px;
        }
        
        .question {
            margin-bottom: 25px;
            page-break-inside: avoid;
        }
        
        .question-number {
            font-weight: bold;
            font-size: 16px;
            margin-bottom: 10px;
        }
        
        .question-text {
            margin-bottom: 15px;
            font-size: 14px;
        }
        
        .options {
            margin-left: 20px;
        }
        
        .option {
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        .option-letter {
            font-weight: bold;
            margin-right: 8px;
        }
        
        .answer-key {
            margin-top: 40px;
            page-break-before: always;
            border-top: 2px solid #333;
            padding-top: 20px;
        }
        
        .answer-key h2 {
            color: #333;
            margin-bottom: 20px;
        }
        
        .answer-item {
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 12px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 20px;
        }
        
        @media print {
            body { margin: 0; padding: 15px; }
            .page-break { page-break-before: always; }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">{{ $quiz->title }}</div>
        <div class="exam-info">
            <div>Date: {{ $examDate }}</div>
            <div>Time Limit: {{ $timeLimit }}</div>
            <div>Total Questions: {{ $totalQuestions }}</div>
        </div>
    </div>

    @if($includeInstructions)
        <div class="instructions">
            <h3>Instructions</h3>
            <ul>
                <li>Read each question carefully before answering</li>
                <li>Choose the best answer for each question</li>
                <li>Mark your answers clearly on the answer sheet</li>
                <li>Do not spend too much time on any single question</li>
                <li>Review your answers before submitting</li>
            </ul>
        </div>
    @endif

    <div class="questions">
        @foreach($questions as $index => $question)
            <div class="question">
                <div class="question-number">{{ $index + 1 }}.</div>
                <div class="question-text">{{ $question->title }}</div>
                <div class="options">
                    @foreach($question->answers as $answerIndex => $answer)
                        <div class="option">
                            <span class="option-letter">{{ chr(65 + $answerIndex) }}.</span>
                            {{ $answer->title }}
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>

    @if($includeAnswerKey && !empty($answerKey))
        <div class="answer-key">
            <h2>Answer Key</h2>
            @foreach($answerKey as $questionNum => $answer)
                <div class="answer-item">
                    <strong>Question {{ $questionNum }}:</strong> {{ $answer }}
                </div>
            @endforeach
        </div>
    @endif

    @if($includeTimestamp)
        <div class="footer">
            <div>Generated on: {{ $exportTimestamp }}</div>
            <div>Powered by QuizWhiz AI</div>
        </div>
    @endif
</body>
</html>
