<!-- ExamGenerator.ai - Latest Generated Exams Section -->
<div class="exam-showcase-section">
    <!-- Header Section -->
    <div class="exam-header">
        <div class="exam-nav-pill">
            <span class="nav-pill">Exams</span>
        </div>
        <h1 class="exam-main-title">Latest Generated Exams</h1>
        <p class="exam-description">
            See examples of AI-generated exams from various domains. Our technology adapts to any subject matter or content type.
        </p>
    </div>

    <!-- Category Filters -->
    <div class="exam-categories">
        <div class="category-filters">
            <button class="category-btn active" data-category="all">All Exams</button>
            <button class="category-btn" data-category="history">History</button>
            <button class="category-btn" data-category="science">Science & Technology</button>
            <button class="category-btn" data-category="mathematics">Mathematics</button>
            <button class="category-btn" data-category="literature">Literature</button>
            <button class="category-btn" data-category="geography">Geography</button>
            <button class="category-btn" data-category="current-affairs">Current Affairs</button>
            <button class="category-btn" data-category="entertainment">Entertainment</button>
            <button class="category-btn" data-category="sports">Sports</button>
            <button class="category-btn" data-category="art-culture">Art & Culture</button>
        </div>
    </div>

    <!-- Exam Cards -->
    <div class="exam-cards-container">
        @foreach($exams as $exam)
        <div class="exam-card" data-category="{{ $exam->category }}">
            <!-- Card Header -->
            <div class="exam-card-header">
                <div class="exam-badges">
                    <span class="featured-badge">Featured • {{ $exam->category_name }}</span>
                </div>
                <div class="exam-stats">
                    <span class="question-count">
                        <i class="fas fa-file-alt"></i>
                        {{ $exam->questions_count }} questions
                    </span>
                </div>
            </div>

            <!-- Exam Title -->
            <div class="exam-title-section">
                <h3 class="exam-id">{{ $exam->id }}</h3>
                <h2 class="exam-name">{{ $exam->title }}</h2>
            </div>

            <!-- Topics Covered -->
            <div class="exam-topics">
                <p class="topics-label">Topics covered:</p>
                <div class="topic-tags">
                    @foreach($exam->topics as $topic)
                    <span class="topic-tag">{{ $topic }}</span>
                    @endforeach
                </div>
            </div>

            <!-- Card Footer -->
            <div class="exam-card-footer">
                <div class="exam-author">
                    <img src="{{ $exam->user->avatar ?? '/images/default-avatar.png' }}" 
                         alt="Author" class="author-avatar">
                    <span class="author-name">{{ $exam->user->name }}</span>
                    <span class="generated-date">Generated {{ $exam->created_at->diffForHumans() }}</span>
                </div>
                <div class="exam-actions">
                    <button class="preview-exam-btn" onclick="previewExam({{ $exam->id }})">
                        <i class="fas fa-eye"></i>
                        Preview Exam
                    </button>
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>

<style>
/* ExamGenerator.ai Custom Styles */
.exam-showcase-section {
    padding: 2rem;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
}

.exam-header {
    text-align: center;
    margin-bottom: 3rem;
}

.exam-nav-pill {
    margin-bottom: 1rem;
}

.nav-pill {
    background: rgba(255, 255, 255, 0.2);
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 25px;
    font-size: 0.9rem;
    backdrop-filter: blur(10px);
}

.exam-main-title {
    color: white;
    font-size: 3rem;
    font-weight: bold;
    margin-bottom: 1rem;
    text-shadow: 0 2px 4px rgba(0,0,0,0.3);
}

.exam-description {
    color: rgba(255, 255, 255, 0.9);
    font-size: 1.2rem;
    max-width: 600px;
    margin: 0 auto;
    line-height: 1.6;
}

.exam-categories {
    margin-bottom: 3rem;
}

.category-filters {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    justify-content: center;
}

.category-btn {
    background: rgba(255, 255, 255, 0.1);
    color: white;
    border: none;
    padding: 0.8rem 1.5rem;
    border-radius: 25px;
    cursor: pointer;
    transition: all 0.3s ease;
    backdrop-filter: blur(10px);
    font-weight: 500;
}

.category-btn:hover {
    background: rgba(255, 255, 255, 0.2);
    transform: translateY(-2px);
}

.category-btn.active {
    background: rgba(255, 255, 255, 0.3);
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
}

.exam-cards-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 2rem;
    max-width: 1200px;
    margin: 0 auto;
}

.exam-card {
    background: white;
    border-radius: 15px;
    padding: 2rem;
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    transition: transform 0.3s ease;
}

.exam-card:hover {
    transform: translateY(-5px);
}

.exam-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.featured-badge {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
}

.question-count {
    background: #e3f2fd;
    color: #1976d2;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 500;
}

.exam-title-section {
    margin-bottom: 1.5rem;
}

.exam-id {
    color: #666;
    font-size: 1rem;
    margin-bottom: 0.5rem;
}

.exam-name {
    color: #333;
    font-size: 1.8rem;
    font-weight: bold;
    margin: 0;
}

.exam-topics {
    margin-bottom: 2rem;
}

.topics-label {
    color: #666;
    margin-bottom: 0.8rem;
    font-weight: 500;
}

.topic-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.topic-tag {
    background: #e3f2fd;
    color: #1976d2;
    padding: 0.4rem 0.8rem;
    border-radius: 15px;
    font-size: 0.8rem;
    font-weight: 500;
}

.exam-card-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.exam-author {
    display: flex;
    align-items: center;
    gap: 0.8rem;
}

.author-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
}

.author-name {
    font-weight: 600;
    color: #333;
}

.generated-date {
    color: #666;
    font-size: 0.9rem;
}

.preview-exam-btn {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    padding: 0.8rem 1.5rem;
    border-radius: 25px;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.3s ease;
}

.preview-exam-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
}

@media (max-width: 768px) {
    .exam-main-title {
        font-size: 2rem;
    }
    
    .exam-cards-container {
        grid-template-columns: 1fr;
    }
    
    .category-filters {
        justify-content: flex-start;
        overflow-x: auto;
    }
}
</style>

<script>
function previewExam(examId) {
    // Redirect to exam preview page
    window.location.href = `/exam/${examId}/preview`;
}

// Category filtering
document.querySelectorAll('.category-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        // Remove active class from all buttons
        document.querySelectorAll('.category-btn').forEach(b => b.classList.remove('active'));
        // Add active class to clicked button
        this.classList.add('active');
        
        const category = this.dataset.category;
        filterExams(category);
    });
});

function filterExams(category) {
    const examCards = document.querySelectorAll('.exam-card');
    
    examCards.forEach(card => {
        if (category === 'all' || card.dataset.category === category) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
}
</script>
