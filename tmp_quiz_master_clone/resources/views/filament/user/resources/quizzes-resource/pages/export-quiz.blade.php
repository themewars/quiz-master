<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Quiz Info Card -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center space-x-4">
                <div class="flex-shrink-0">
                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                </div>
                <div class="flex-1">
                    <h3 class="text-lg font-semibold text-gray-900">{{ $record->title }}</h3>
                    <p class="text-sm text-gray-500">
                        {{ $record->questions()->count() }} Questions •
                        {{ $record->quizUser()->count() }} Students •
                        Created {{ $record->created_at->diffForHumans() }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Export Options -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Export Options</h3>
            
            <!-- Export Settings -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
                <!-- Answer Key Toggle -->
                <div class="p-4 bg-gray-50 rounded-lg">
                    <div class="flex items-center space-x-3">
                        <input type="checkbox" 
                               wire:model="includeAnswerKey" 
                               id="includeAnswerKey"
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="includeAnswerKey" class="text-sm font-medium text-gray-700">
                            Include Answer Key
                        </label>
                    </div>
                    <p class="text-xs text-gray-500 mt-1 ml-7">
                        Include correct answers
                    </p>
                </div>

                <!-- Instructions Toggle -->
                <div class="p-4 bg-gray-50 rounded-lg">
                    <div class="flex items-center space-x-3">
                        <input type="checkbox" 
                               wire:model="includeInstructions" 
                               id="includeInstructions"
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="includeInstructions" class="text-sm font-medium text-gray-700">
                            Include Instructions
                        </label>
                    </div>
                    <p class="text-xs text-gray-500 mt-1 ml-7">
                        Add exam instructions
                    </p>
                </div>

                <!-- Student Info Toggle -->
                <div class="p-4 bg-gray-50 rounded-lg">
                    <div class="flex items-center space-x-3">
                        <input type="checkbox" 
                               wire:model="includeStudentInfo" 
                               id="includeStudentInfo"
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="includeStudentInfo" class="text-sm font-medium text-gray-700">
                            Student Info Fields
                        </label>
                    </div>
                    <p class="text-xs text-gray-500 mt-1 ml-7">
                        Name, ID, Date fields
                    </p>
                </div>

                <!-- Font Size Selection -->
                <div class="p-4 bg-gray-50 rounded-lg">
                    <label class="text-sm font-medium text-gray-700 mb-2 block">Font Size</label>
                    <select wire:model="fontSize" class="w-full text-sm border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                        <option value="small">Small (Compact)</option>
                        <option value="medium">Medium (Standard)</option>
                        <option value="large">Large (Accessible)</option>
                    </select>
                </div>

                <!-- Page Size Selection -->
                <div class="p-4 bg-gray-50 rounded-lg">
                    <label class="text-sm font-medium text-gray-700 mb-2 block">Page Size</label>
                    <select wire:model="pageSize" class="w-full text-sm border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                        <option value="A4">A4 (Standard)</option>
                        <option value="A3">A3 (Large)</option>
                        <option value="Letter">Letter (US)</option>
                    </select>
                </div>

                <!-- Orientation Selection -->
                <div class="p-4 bg-gray-50 rounded-lg">
                    <label class="text-sm font-medium text-gray-700 mb-2 block">Orientation</label>
                    <select wire:model="orientation" class="w-full text-sm border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                        <option value="portrait">Portrait</option>
                        <option value="landscape">Landscape</option>
                    </select>
                </div>
            </div>
            
            <!-- Export Buttons -->
            <div class="space-y-4">
                <h4 class="text-md font-semibold text-gray-800">Standard Exports</h4>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <x-filament::button color="success" icon="heroicon-o-document-arrow-down" wire:click="exportPDF" class="w-full">
                        Export PDF
                    </x-filament::button>
                    
                    <x-filament::button color="primary" icon="heroicon-o-document-text" wire:click="exportWord" class="w-full">
                        Export Word
                    </x-filament::button>
                    
                    <x-filament::button color="warning" icon="heroicon-o-code-bracket" wire:click="exportHTML" class="w-full">
                        Export HTML
                    </x-filament::button>
                </div>

                <h4 class="text-md font-semibold text-gray-800 mt-6">Specialized PDF Exports</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <x-filament::button color="gray" icon="heroicon-o-document-arrow-down" wire:click="exportCompactPDF" class="w-full">
                        Compact PDF
                    </x-filament::button>
                    
                    <x-filament::button color="blue" icon="heroicon-o-document-arrow-down" wire:click="exportLargePDF" class="w-full">
                        Large Font PDF
                    </x-filament::button>
                    
                    <x-filament::button color="green" icon="heroicon-o-document-arrow-down" wire:click="exportLandscapePDF" class="w-full">
                        Landscape PDF
                    </x-filament::button>
                    
                    <x-filament::button color="purple" icon="heroicon-o-document-arrow-down" wire:click="exportA3PDF" class="w-full">
                        A3 Size PDF
                    </x-filament::button>
                </div>
            </div>
        </div>

        <!-- Export Features -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-lg p-6 text-white">
                <div class="flex items-center space-x-3">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                    </svg>
                    <div>
                        <h4 class="font-semibold">PDF Export</h4>
                        <p class="text-sm opacity-90">Multiple sizes & layouts</p>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-r from-green-500 to-green-600 rounded-lg p-6 text-white">
                <div class="flex items-center space-x-3">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <div>
                        <h4 class="font-semibold">Word Export</h4>
                        <p class="text-sm opacity-90">Editable documents</p>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-r from-purple-500 to-purple-600 rounded-lg p-6 text-white">
                <div class="flex items-center space-x-3">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path>
                    </svg>
                    <div>
                        <h4 class="font-semibold">HTML Export</h4>
                        <p class="text-sm opacity-90">Web-ready format</p>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-r from-orange-500 to-orange-600 rounded-lg p-6 text-white">
                <div class="flex items-center space-x-3">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4"></path>
                    </svg>
                    <div>
                        <h4 class="font-semibold">Customization</h4>
                        <p class="text-sm opacity-90">Font, size, layout options</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Export Tips -->
        <div class="bg-gradient-to-r from-indigo-50 to-blue-50 rounded-lg p-6 border border-indigo-200">
            <h4 class="text-lg font-semibold text-indigo-900 mb-3">💡 Export Tips</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-indigo-800">
                <div class="flex items-start space-x-2">
                    <span class="text-indigo-600">•</span>
                    <span><strong>Compact PDF:</strong> Perfect for printing multiple exams on one page</span>
                </div>
                <div class="flex items-start space-x-2">
                    <span class="text-indigo-600">•</span>
                    <span><strong>Landscape:</strong> Great for exams with many multiple choice options</span>
                </div>
                <div class="flex items-start space-x-2">
                    <span class="text-indigo-600">•</span>
                    <span><strong>A3 Size:</strong> Ideal for large printouts and presentations</span>
                </div>
                <div class="flex items-start space-x-2">
                    <span class="text-indigo-600">•</span>
                    <span><strong>Large Font:</strong> Better accessibility for students with vision needs</span>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>