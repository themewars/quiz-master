<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
    <div class="flex items-center justify-between mb-4">
        <div class="flex items-center">
            @if($isCreating)
                <div class="animate-spin rounded-full h-5 w-5 border-b-2 border-blue-600 mr-3"></div>
            @else
                <div class="h-5 w-5 mr-3"></div>
            @endif
            <div>
                <h3 class="text-lg font-semibold text-gray-900">
                    {{ $isCreating ? 'Creating Exam...' : 'Exam Creation' }}
                </h3>
                <p class="text-sm text-gray-600">{{ $currentStep }}</p>
            </div>
        </div>
        
        @if($isCreating && $totalQuestions > 0)
            <div class="text-right">
                <div class="text-sm font-medium text-gray-900">
                    {{ $questionsGenerated }}/{{ $totalQuestions }} Questions
                </div>
                <div class="text-xs text-gray-500">
                    {{ $totalQuestions > 0 ? round(($questionsGenerated / $totalQuestions) * 100) : 0 }}% Complete
                </div>
            </div>
        @endif
    </div>

    @if($isCreating && $totalQuestions > 0)
        <div class="w-full bg-gray-200 rounded-full h-2 mb-4">
            <div class="bg-blue-600 h-2 rounded-full transition-all duration-300 ease-in-out" 
                 style="width: {{ $totalQuestions > 0 ? ($questionsGenerated / $totalQuestions) * 100 : 0 }}%"></div>
        </div>
    @endif

    @if($progressMessage)
        <div class="text-sm text-gray-600">
            {{ $progressMessage }}
        </div>
    @endif

    @if(!$isCreating && $currentStep === 'Exam created successfully!')
        <div class="mt-4 p-3 bg-green-50 border border-green-200 rounded-md">
            <div class="flex items-center">
                <svg class="h-5 w-5 text-green-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                </svg>
                <span class="text-sm font-medium text-green-800">Exam created successfully!</span>
            </div>
            <p class="text-sm text-green-700 mt-1">Redirecting to edit page...</p>
        </div>
    @endif

    @if(!$isCreating && $currentStep === 'Error occurred')
        <div class="mt-4 p-3 bg-red-50 border border-red-200 rounded-md">
            <div class="flex items-center">
                <svg class="h-5 w-5 text-red-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                </svg>
                <span class="text-sm font-medium text-red-800">Error occurred</span>
            </div>
            <p class="text-sm text-red-700 mt-1">Failed to create exam. Please try again.</p>
        </div>
    @endif
</div>
