<div id="live-progress-container" class="mb-6" style="display: none;">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4">
        <div class="flex items-center justify-between mb-2">
            <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100">
                {{ __('Generating Exam Questions...') }}
            </h3>
            <span id="progress-text" class="text-sm text-gray-600 dark:text-gray-400">
                0/0 (0%)
            </span>
        </div>
        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2.5">
            <div id="progress-bar" class="bg-blue-600 h-2.5 rounded-full transition-all duration-300 ease-in-out" style="width: 0%"></div>
        </div>
        <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">
            {{ __('Please wait while questions are being generated in the background...') }}
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let progressCheckInterval;
    let currentQuizId = null;

    function startProgressMonitoring() {
        const container = document.getElementById('live-progress-container');
        if (container) {
            container.style.display = 'block';
        }
        progressCheckInterval = setInterval(checkProgress, 2000);
    }

    function stopProgressMonitoring() {
        if (progressCheckInterval) {
            clearInterval(progressCheckInterval);
            progressCheckInterval = null;
        }
        const container = document.getElementById('live-progress-container');
        if (container) {
            container.style.display = 'none';
        }
    }

    function checkProgress() {
        fetch('/api/quiz-progress', {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.quiz) {
                currentQuizId = data.quiz.id;
                updateProgressBar(data.quiz);
                
                if (data.quiz.status === 'completed') {
                    setTimeout(() => {
                        window.location.href = `/user/quizzes/${data.quiz.id}/edit`;
                    }, 1000);
                }
            } else {
                stopProgressMonitoring();
            }
        })
        .catch(error => {
            console.error('Error checking progress:', error);
        });
    }

    function updateProgressBar(quiz) {
        const progressBar = document.getElementById('progress-bar');
        const progressText = document.getElementById('progress-text');
        
        if (progressBar && progressText) {
            const percentage = quiz.progress_total > 0 ? Math.round((quiz.progress_done / quiz.progress_total) * 100) : 0;
            progressBar.style.width = percentage + '%';
            progressText.textContent = `${quiz.progress_done}/${quiz.progress_total} (${percentage}%)`;
        }
    }

    // Listen for form submission
    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function() {
            setTimeout(startProgressMonitoring, 1000);
        });
    }
    
    // Check if there's already a processing quiz on page load
    setTimeout(() => {
        checkProgress();
        if (currentQuizId) {
            startProgressMonitoring();
        }
    }, 500);
});
</script>
