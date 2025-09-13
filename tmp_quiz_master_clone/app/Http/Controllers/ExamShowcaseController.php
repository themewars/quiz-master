<?php

namespace App\Http\Controllers;

use App\Models\Quiz;
use App\Models\Category;
use Illuminate\Http\Request;

class ExamShowcaseController extends Controller
{
    /**
     * Display the exam showcase page
     */
    public function index(Request $request)
    {
        $category = $request->get('category', 'all');
        
        // Get exams with relationships
        $query = Quiz::with(['user', 'category', 'questions'])
            ->where('status', 1)
            ->where('is_show_home', 1);
        
        // Filter by category if not 'all'
        if ($category !== 'all') {
            $query->whereHas('category', function($q) use ($category) {
                $q->where('slug', $category);
            });
        }
        
        $exams = $query->orderBy('created_at', 'desc')
            ->paginate(12);
        
        // Get categories for filter
        $categories = Category::where('status', 1)->get();
        
        // Transform exams data
        $exams->getCollection()->transform(function($exam) {
            $exam->topics = $this->extractTopics($exam);
            $exam->questions_count = $exam->questions->count();
            $exam->category_name = $exam->category->name ?? 'General';
            return $exam;
        });
        
        return view('exam-showcase', compact('exams', 'categories', 'category'));
    }
    
    /**
     * Extract topics from exam description
     */
    private function extractTopics($exam)
    {
        // Extract topics from description or use predefined topics
        $topics = [];
        
        if ($exam->quiz_description) {
            // Simple topic extraction (you can enhance this)
            $description = strtolower($exam->quiz_description);
            
            $commonTopics = [
                'biology', 'chemistry', 'physics', 'mathematics', 'history',
                'geography', 'literature', 'science', 'technology', 'art',
                'culture', 'sports', 'entertainment', 'current affairs'
            ];
            
            foreach ($commonTopics as $topic) {
                if (strpos($description, $topic) !== false) {
                    $topics[] = ucfirst($topic);
                }
            }
        }
        
        // If no topics found, use category name
        if (empty($topics)) {
            $topics = [$exam->category->name ?? 'General Knowledge'];
        }
        
        return array_slice($topics, 0, 4); // Limit to 4 topics
    }
    
    /**
     * Preview a specific exam
     */
    public function preview($id)
    {
        $exam = Quiz::with(['questions.answers', 'user', 'category'])
            ->where('id', $id)
            ->where('status', 1)
            ->firstOrFail();
        
        return view('exam-preview', compact('exam'));
    }
    
    /**
     * Get exam data for AJAX requests
     */
    public function getExams(Request $request)
    {
        $category = $request->get('category', 'all');
        $page = $request->get('page', 1);
        
        $query = Quiz::with(['user', 'category', 'questions'])
            ->where('status', 1)
            ->where('is_show_home', 1);
        
        if ($category !== 'all') {
            $query->whereHas('category', function($q) use ($category) {
                $q->where('slug', $category);
            });
        }
        
        $exams = $query->orderBy('created_at', 'desc')
            ->paginate(12, ['*'], 'page', $page);
        
        // Transform exams data
        $exams->getCollection()->transform(function($exam) {
            $exam->topics = $this->extractTopics($exam);
            $exam->questions_count = $exam->questions->count();
            $exam->category_name = $exam->category->name ?? 'General';
            return $exam;
        });
        
        return response()->json([
            'exams' => $exams->items(),
            'pagination' => [
                'current_page' => $exams->currentPage(),
                'last_page' => $exams->lastPage(),
                'total' => $exams->total(),
                'has_more' => $exams->hasMorePages()
            ]
        ]);
    }
}
