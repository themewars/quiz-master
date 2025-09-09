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
                        {{ $record->quizUser()->count() }} Participants • 
                        Created {{ $record->created_at->diffForHumans() }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Export Options -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Export Options</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <!-- Include Instructions -->
                <div class="flex items-center space-x-3">
                    <input type="checkbox" 
                           wire:model.live="includeInstructions" 
                           id="includeInstructions"
                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                    <label for="includeInstructions" class="text-sm font-medium text-gray-700">
                        Include Instructions
                    </label>
                </div>
                
                <!-- Include Answer Key -->
                <div class="flex items-center space-x-3">
                    <input type="checkbox" 
                           wire:model.live="includeAnswerKey" 
                           id="includeAnswerKey"
                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                    <label for="includeAnswerKey" class="text-sm font-medium text-gray-700">
                        Include Answer Key
                    </label>
                </div>
                
                <!-- Export Template -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Export Template</label>
                    <select wire:model.live="exportTemplate" 
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        <option value="standard">Standard</option>
                        <option value="academic">Academic</option>
                        <option value="professional">Professional</option>
                        <option value="minimal">Minimal</option>
                    </select>
                </div>
                
                <!-- Font Size -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Font Size</label>
                    <select wire:model.live="fontSize" 
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        <option value="small">Small (10pt)</option>
                        <option value="medium">Medium (12pt)</option>
                        <option value="large">Large (14pt)</option>
                    </select>
                </div>
            </div>
            
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
        </div>

        <!-- Preview Section -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Live Preview</h3>
            <div class="prose max-w-none border border-gray-200 rounded-lg p-4 bg-gray-50">
                <div wire:key="preview-{{ $this->includeInstructions }}-{{ $this->includeAnswerKey }}-{{ $this->exportTemplate }}-{{ $this->fontSize }}">
                    {!! $this->previewHtml !!}
                </div>
            </div>
        </div>

        <!-- Export Features -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-lg p-6 text-white">
                <div class="flex items-center space-x-3">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                    </svg>
                    <div>
                        <h4 class="font-semibold">PDF Export</h4>
                        <p class="text-sm opacity-90">Professional exam papers</p>
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
        </div>
    </div>
</x-filament-panels::page>
