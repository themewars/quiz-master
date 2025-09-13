console.log("Edit page progress bar script loaded");

// Add progress bar HTML to the page
var progressBarHTML = "<div id=\"live-progress-container\" style=\"display: none; position: fixed; top: 0; left: 0; right: 0; z-index: 9999; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 8px 16px; box-shadow: 0 2px 8px rgba(0,0,0,0.15);\"><div class=\"flex items-center justify-between\"><div class=\"flex items-center\"><div class=\"animate-spin rounded-full h-4 w-4 border-2 border-white border-t-transparent mr-3\"></div><div><div class=\"text-sm font-medium\">Generating Exam Questions...</div><div class=\"text-xs opacity-90\">Please wait while questions are being generated in the background...</div></div></div><div class=\"flex items-center\"><span id=\"progress-text\" class=\"text-sm font-semibold bg-blue-500 text-white px-2 py-1 rounded\">0/0 (0%)</span></div></div><div class=\"mt-2 w-full bg-white bg-opacity-20 rounded-full h-1\"><div id=\"progress-bar\" class=\"bg-white h-1 rounded-full transition-all duration-300 ease-in-out\" style=\"width: 0%\"></div></div></div>";

function initializeProgressBar() {
    console.log("Edit page - Adding progress bar to page top...");
    
    // Add progress bar at the very top of the page body
    var body = document.querySelector("body");
    if (body) {
        console.log("Found body element, adding progress bar at top");
        body.insertAdjacentHTML("afterbegin", progressBarHTML);
        console.log("Progress bar added to page top");
        
        // Progress bar is already styled inline
        var addedBar = document.getElementById("live-progress-container");
        if (addedBar) {
            console.log("Progress bar styled as fixed top banner");
        }
        
        // Check if progress bar was actually added
        console.log("Progress bar element:", addedBar);
        if (addedBar) {
            console.log("Progress bar is visible:", addedBar.style.display);
            console.log("Progress bar computed style:", window.getComputedStyle(addedBar).display);
            
            // Force make it visible
            addedBar.style.display = "block";
            console.log("Progress bar made visible");
        }
        
        // Add progress monitoring functionality
        var progressCheckInterval;
        var currentQuizId = null;
        var isRedirecting = false;

        function startProgressMonitoring() {
            console.log("Edit page - Starting progress monitoring");
            var container = document.getElementById("live-progress-container");
            if (container) {
                container.style.display = "block";
                console.log("Progress bar made visible");
            }
            progressCheckInterval = setInterval(checkProgress, 2000);
            console.log("Progress monitoring interval started");
        }

        function stopProgressMonitoring() {
            console.log("🛑 Stopping progress monitoring...");
            if (progressCheckInterval) {
                clearInterval(progressCheckInterval);
                progressCheckInterval = null;
                console.log("Progress interval cleared");
            }
            var container = document.getElementById("live-progress-container");
            if (container) {
                container.style.display = "none";
                console.log("Progress container hidden");
            }
            // Clear any pending timeouts
            currentQuizId = null;
            console.log("Progress monitoring stopped completely");
        }

        function checkProgress() {
            console.log("Checking progress...");
            fetch("/api/quiz-progress", {
                method: "GET",
                headers: {
                    "X-Requested-With": "XMLHttpRequest",
                    "X-CSRF-TOKEN": document.querySelector("meta[name=\"csrf-token\"]").getAttribute("content")
                }
            })
            .then(function(response) {
                console.log("API response status:", response.status);
                return response.json();
            })
            .then(function(data) {
                console.log("Progress API response:", data);
                if (data.quiz) {
                    currentQuizId = data.quiz.id;
                    updateProgressBar(data.quiz);
                    
                    console.log("Quiz status:", data.quiz.status);
                    console.log("Progress:", data.quiz.progress_done + "/" + data.quiz.progress_total);
                    
                    // Check if exam is completed (multiple conditions)
                    var isCompleted = data.quiz.status === "completed" || 
                                       (data.quiz.progress_done >= data.quiz.progress_total && data.quiz.progress_total > 0) ||
                                       (data.quiz.question_count >= data.quiz.progress_total && data.quiz.progress_total > 0) ||
                                       (data.quiz.question_count > 0 && data.quiz.status === "processing" && data.quiz.progress_total === 0 && data.quiz.question_count >= 10);
                    
                    console.log("Completion check:", {
                        status: data.quiz.status,
                        progress_done: data.quiz.progress_done,
                        progress_total: data.quiz.progress_total,
                        question_count: data.quiz.question_count,
                        isCompleted: isCompleted
                    });
                    
                    if (isCompleted && !isRedirecting) {
                        isRedirecting = true;
                        console.log("🎉 EXAM COMPLETED! Starting redirect process...");
                        
                        // Show completion message
                        var progressText = document.getElementById("progress-text");
                        if (progressText) {
                            progressText.textContent = "✅ Completed! Redirecting...";
                            progressText.style.background = "#10b981"; // Green background
                        }
                        
                        // Stop monitoring immediately
                        stopProgressMonitoring();
                        
                        // Clear any existing intervals
                        if (progressCheckInterval) {
                            clearInterval(progressCheckInterval);
                            progressCheckInterval = null;
                        }
                        
                        // Redirect after short delay
                        setTimeout(function() {
                            console.log("🔄 Redirecting to exam edit page...");
                            console.log("Current URL:", window.location.href);
                            
                            // Force reload the page to show completed exam
                            window.location.href = window.location.href;
                        }, 1500);
                    }
                } else {
                    console.log("No processing quiz found, stopping monitoring");
                    stopProgressMonitoring();
                }
            })
            .catch(function(error) {
                console.error("Error checking progress:", error);
                console.log("Retrying in 3 seconds...");
                setTimeout(checkProgress, 3000);
            });
        }

        function updateProgressBar(quiz) {
            console.log("Updating progress bar with:", quiz);
            var progressBar = document.getElementById("progress-bar");
            var progressText = document.getElementById("progress-text");
            
            if (progressBar && progressText) {
                var percentage = quiz.progress_total > 0 ? Math.round((quiz.progress_done / quiz.progress_total) * 100) : 0;
                progressBar.style.width = percentage + "%";
                progressText.textContent = quiz.progress_done + "/" + quiz.progress_total + " (" + percentage + "%)";
                console.log("Progress updated:", quiz.progress_done + "/" + quiz.progress_total + " (" + percentage + "%)");
            } else {
                console.log("Progress bar elements not found:", {progressBar: progressBar, progressText: progressText});
            }
        }
        
        // Check if there is already a processing quiz
        setTimeout(function() {
            console.log("Initial progress check...");
            checkProgress();
            if (currentQuizId) {
                console.log("Found processing quiz, starting monitoring");
                startProgressMonitoring();
            } else {
                console.log("No processing quiz found, checking current quiz...");
                // If no processing quiz found, check if this quiz has 0 questions (might be processing)
                var url = window.location.pathname;
                var quizId = url.match(/\/quizzes\/(\d+)\//);
                if (quizId) {
                    console.log("Checking quiz status for ID:", quizId[1]);
                    fetch("/api/quiz-status/" + quizId[1])
                    .then(function(response) { return response.json(); })
                    .then(function(data) {
                        console.log("Quiz status response:", data);
                        if (data.quiz && data.quiz.generation_status === "processing") {
                            console.log("Found processing quiz, starting monitoring");
                            startProgressMonitoring();
                        } else {
                            console.log("Quiz not processing, not starting monitoring");
                            // Don't start monitoring if quiz is not processing
                        }
                    })
                    .catch(function(error) {
                        console.error("Error checking quiz status:", error);
                        console.log("Not starting monitoring due to error");
                        // Don't start monitoring on error
                    });
                } else {
                    console.log("No quiz ID found in URL, not starting monitoring");
                    // Don't start monitoring if no quiz ID
                }
            }
        }, 500);
    } else {
        console.log("Edit page - No target element found, retrying in 500ms");
        setTimeout(initializeProgressBar, 500);
    }
}

// Try immediately, then on DOM ready, then retry if needed
initializeProgressBar();

if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initializeProgressBar);
}
