<?php

namespace App\Services;

use App\Models\Quiz;
use App\Models\Question;
use App\Models\Answer;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Style\Font;
use PhpOffice\PhpWord\Style\Paragraph;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class ExamExportService
{
    /**
     * Export quiz as exam paper in multiple formats
     */
    public function exportExamPaper(Quiz $quiz, $format = 'pdf', $template = 'standard', bool $includeInstructions = true, bool $includeAnswerKey = false, string $fontSize = 'medium', string $pageSize = 'A4', string $orientation = 'portrait', bool $compactMode = false, bool $includeStudentInfo = true, bool $includeTimestamp = true)
    {
        switch ($format) {
            case 'pdf':
                return $this->exportToPDF($quiz, $template, $includeInstructions, $includeAnswerKey, $fontSize, $pageSize, $orientation, $compactMode, $includeStudentInfo, $includeTimestamp);
            case 'word':
                return $this->exportToWord($quiz, $template, $includeInstructions, $includeAnswerKey, $fontSize, $pageSize, $orientation, $compactMode, $includeStudentInfo, $includeTimestamp);
            case 'html':
                return $this->exportToHTML($quiz, $template, $includeInstructions, $includeAnswerKey, $fontSize, $pageSize, $orientation, $compactMode, $includeStudentInfo, $includeTimestamp);
            default:
                throw new \InvalidArgumentException('Unsupported export format');
        }
    }

    /**
     * Generate preview HTML
     */
    public function generatePreviewHtml(Quiz $quiz, $template = 'standard', bool $includeInstructions = true, bool $includeAnswerKey = false, string $fontSize = 'medium')
    {
        $data = $this->prepareExamData($quiz, $includeInstructions, $includeAnswerKey, $fontSize, false, true, true);
        
        return view("exports.exam.{$template}", $data)->render();
    }

    /**
     * Export to PDF
     */
    protected function exportToPDF(Quiz $quiz, $template, bool $includeInstructions, bool $includeAnswerKey, string $fontSize, string $pageSize, string $orientation, bool $compactMode, bool $includeStudentInfo, bool $includeTimestamp)
    {
        $data = $this->prepareExamData($quiz, $includeInstructions, $includeAnswerKey, $fontSize, $compactMode, $includeStudentInfo, $includeTimestamp);
        
        $pdf = Pdf::loadView("exports.exam.{$template}", $data)
            ->setPaper($pageSize, $orientation)
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'defaultFont' => 'DejaVu Sans',
            ]);

        $filename = $this->generateFilename($quiz, 'pdf');
        $filePath = "exports/{$filename}";
        
        Storage::disk('public')->put($filePath, $pdf->output());
        
        return [
            'success' => true,
            'file_path' => $filePath,
            'download_url' => Storage::disk('public')->url($filePath),
            'filename' => $filename,
        ];
    }

    /**
     * Export to Word
     */
    protected function exportToWord(Quiz $quiz, $template, bool $includeInstructions, bool $includeAnswerKey, string $fontSize, string $pageSize, string $orientation, bool $compactMode, bool $includeStudentInfo, bool $includeTimestamp)
    {
        $data = $this->prepareExamData($quiz, $includeInstructions, $includeAnswerKey, $fontSize, $compactMode, $includeStudentInfo, $includeTimestamp);
        
        $phpWord = new PhpWord();
        $phpWord->setDefaultFontName('Arial');
        $phpWord->setDefaultFontSize(12);
        
        $section = $phpWord->addSection([
            'marginTop' => 1134,
            'marginBottom' => 1134,
            'marginLeft' => 1134,
            'marginRight' => 1134,
        ]);

        // Add title
        $section->addText($quiz->title, ['bold' => true, 'size' => 16]);
        $section->addTextBreak();

        // Add instructions if requested
        if ($includeInstructions) {
            $section->addText('Instructions:', ['bold' => true, 'size' => 12]);
            $section->addText('• Read each question carefully before answering');
            $section->addText('• Choose the best answer for each question');
            $section->addText('• Mark your answers clearly');
            $section->addTextBreak();
        }

        // Add questions
        foreach ($data['questions'] as $index => $question) {
            $section->addText(($index + 1) . '. ' . $question->title, ['bold' => true]);
            
            foreach ($question->answers as $answerIndex => $answer) {
                $option = chr(65 + $answerIndex); // A, B, C, D
                $section->addText("   {$option}. {$answer->title}");
            }
            $section->addTextBreak();
        }

        // Add answer key if requested
        if ($includeAnswerKey && !empty($data['answerKey'])) {
            $section->addPageBreak();
            $section->addText('Answer Key', ['bold' => true, 'size' => 14]);
            $section->addTextBreak();
            
            foreach ($data['answerKey'] as $questionNum => $answer) {
                $section->addText("Question {$questionNum}: {$answer}");
            }
        }

        $filename = $this->generateFilename($quiz, 'docx');
        $filePath = "exports/{$filename}";
        
        $writer = IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save(Storage::disk('public')->path($filePath));
        
        return [
            'success' => true,
            'file_path' => $filePath,
            'download_url' => Storage::disk('public')->url($filePath),
            'filename' => $filename,
        ];
    }

    /**
     * Export to HTML
     */
    protected function exportToHTML(Quiz $quiz, $template, bool $includeInstructions, bool $includeAnswerKey, string $fontSize, string $pageSize, string $orientation, bool $compactMode, bool $includeStudentInfo, bool $includeTimestamp)
    {
        $data = $this->prepareExamData($quiz, $includeInstructions, $includeAnswerKey, $fontSize, $compactMode, $includeStudentInfo, $includeTimestamp);
        
        $html = view("exports.exam.{$template}", $data)->render();
        
        $filename = $this->generateFilename($quiz, 'html');
        $filePath = "exports/{$filename}";
        
        Storage::disk('public')->put($filePath, $html);
        
        return [
            'success' => true,
            'file_path' => $filePath,
            'download_url' => Storage::disk('public')->url($filePath),
            'filename' => $filename,
        ];
    }

    /**
     * Prepare exam data for export
     */
    protected function prepareExamData(Quiz $quiz, bool $includeInstructions, bool $includeAnswerKey = false, string $fontSize = 'medium', bool $compactMode = false, bool $includeStudentInfo = true, bool $includeTimestamp = true)
    {
        $questions = $quiz->questions()->with('answers')->get();
        
        $examData = [
            'quiz' => $quiz,
            'questions' => $questions,
            'totalQuestions' => $questions->count(),
            'examDate' => now()->format('d/m/Y'),
            'timeLimit' => $quiz->time_configuration ? $quiz->time . ' ' . ($quiz->time_type == 1 ? 'minutes per question' : 'minutes total') : 'No time limit',
            'includeInstructions' => $includeInstructions,
            'includeAnswerKey' => $includeAnswerKey,
            'fontSize' => $fontSize,
            'compactMode' => $compactMode,
            'includeStudentInfo' => $includeStudentInfo,
            'includeTimestamp' => $includeTimestamp,
            'exportTimestamp' => now()->format('d/m/Y H:i:s'),
        ];

        // Prepare answer key only if needed
        if ($includeAnswerKey) {
            $answerKey = [];
            foreach ($questions as $index => $question) {
                $correctAnswers = $question->answers()->where('is_correct', true)->get();
                $correctOptions = [];
                
                foreach ($correctAnswers as $answer) {
                    $answerIndex = $question->answers->search(function($item) use ($answer) {
                        return $item->id === $answer->id;
                    });
                    $correctOptions[] = chr(65 + $answerIndex);
                }
                
                $answerKey[$index + 1] = implode(', ', $correctOptions);
            }
            $examData['answerKey'] = $answerKey;
        } else {
            $examData['answerKey'] = [];
        }
        
        return $examData;
    }

    /**
     * Generate filename for export
     */
    protected function generateFilename(Quiz $quiz, string $extension): string
    {
        $title = preg_replace('/[^a-zA-Z0-9_-]/', '_', $quiz->title);
        $timestamp = now()->format('Y-m-d_H-i-s');
        
        return "exam_{$title}_{$timestamp}.{$extension}";
    }

    /**
     * Get available export templates
     */
    public function getAvailableTemplates()
    {
        return [
            'standard' => 'Standard Format',
            'compact' => 'Compact Format',
            'detailed' => 'Detailed Format',
            'minimal' => 'Minimal Format',
        ];
    }

    /**
     * Get available export formats
     */
    public function getAvailableFormats()
    {
        return [
            'pdf' => 'PDF Document',
            'word' => 'Word Document',
            'html' => 'HTML File',
        ];
    }
}