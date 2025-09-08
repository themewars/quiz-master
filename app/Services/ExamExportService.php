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
    public function exportExamPaper(Quiz $quiz, $format = 'pdf', $template = 'standard', bool $includeInstructions = true)
    {
        switch ($format) {
            case 'pdf':
                return $this->exportToPDF($quiz, $template, $includeInstructions);
            case 'word':
                return $this->exportToWord($quiz, $template, $includeInstructions);
            case 'html':
                return $this->exportToHTML($quiz, $template, $includeInstructions);
            default:
                throw new \InvalidArgumentException('Unsupported export format');
        }
    }

    /**
     * Export to PDF format
     */
    protected function exportToPDF(Quiz $quiz, $template, bool $includeInstructions)
    {
        $data = $this->prepareExamData($quiz, $includeInstructions);
        $data['template'] = $template;
        $data['includeInstructions'] = $includeInstructions;
        
        $pdf = Pdf::loadView('exports.exam-paper-pdf', $data);
        $pdf->setPaper('A4', 'portrait');
        
        $filename = 'exam_' . $quiz->unique_code . '_' . time() . '.pdf';
        $filepath = 'exports/' . $filename;
        
        Storage::disk('public')->put($filepath, $pdf->output());
        
        return [
            'filepath' => $filepath,
            'filename' => $filename,
            'download_url' => Storage::disk('public')->url($filepath)
        ];
    }

    /**
     * Export to Word format
     */
    protected function exportToWord(Quiz $quiz, $template, bool $includeInstructions)
    {
        // For now, create a simple HTML file that can be opened in Word
        $data = $this->prepareExamData($quiz, $includeInstructions);
        $data['template'] = $template;
        $data['includeInstructions'] = $includeInstructions;
        
        $html = view('exports.exam-paper-html', $data)->render();
        
        $filename = 'exam_' . $quiz->unique_code . '_' . time() . '.html';
        $filepath = 'exports/' . $filename;
        
        Storage::disk('public')->put($filepath, $html);
        
        return [
            'filepath' => $filepath,
            'filename' => $filename,
            'download_url' => Storage::disk('public')->url($filepath)
        ];
    }

    /**
     * Export to HTML format
     */
    protected function exportToHTML(Quiz $quiz, $template, bool $includeInstructions)
    {
        $data = $this->prepareExamData($quiz, $includeInstructions);
        $data['template'] = $template;
        $data['includeInstructions'] = $includeInstructions;
        
        $html = view('exports.exam-paper-html', $data)->render();
        
        $filename = 'exam_' . $quiz->unique_code . '_' . time() . '.html';
        $filepath = 'exports/' . $filename;
        
        Storage::disk('public')->put($filepath, $html);
        
        return [
            'filepath' => $filepath,
            'filename' => $filename,
            'download_url' => Storage::disk('public')->url($filepath)
        ];
    }

    /**
     * Prepare exam data for export
     */
    protected function prepareExamData(Quiz $quiz, bool $includeInstructions)
    {
        $questions = $quiz->questions()->with('answers')->get();
        
        $examData = [
            'quiz' => $quiz,
            'questions' => $questions,
            'totalQuestions' => $questions->count(),
            'examDate' => now()->format('d/m/Y'),
            'timeLimit' => $quiz->time_configuration ? $quiz->time . ' ' . ($quiz->time_type == 1 ? 'minutes per question' : 'minutes total') : 'No time limit',
            'includeInstructions' => $includeInstructions,
        ];

        // Prepare answer key
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
        
        return $examData;
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
            'word' => 'Microsoft Word',
            'html' => 'HTML Web Page',
        ];
    }
}
