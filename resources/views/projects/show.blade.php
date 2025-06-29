@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-slate-800 mb-2">{{ $project->title }}</h1>
                <p class="text-slate-600">{{ $project->original_filename }}</p>
            </div>
            <div class="flex space-x-3">
                @if($project->hasPDF())
                    <a href="{{ route('projects.download-pdf', $project) }}" 
                       class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-lg font-medium transition-colors duration-200 flex items-center">
                        <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Download PDF
                    </a>
                    <a href="{{ route('projects.preview-pdf', $project) }}" 
                       target="_blank"
                       class="border border-slate-300 text-slate-700 hover:bg-slate-50 px-4 py-2 rounded-lg font-medium transition-colors duration-200 flex items-center">
                        <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                        Preview PDF
                    </a>
                @endif
                
                @if($project->canRegeneratePDF())
                    <form action="{{ route('projects.regenerate-pdf', $project) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" 
                                onclick="return confirm('Regenerate PDF? This will replace the current PDF file.')"
                                class="border border-amber-300 text-amber-700 hover:bg-amber-50 px-4 py-2 rounded-lg font-medium transition-colors duration-200">
                            Regenerate PDF
                        </button>
                    </form>
                @endif
                
                <form action="{{ route('projects.destroy', $project) }}" method="POST" class="inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" 
                            onclick="return confirm('Are you sure you want to delete this project? This action cannot be undone.')"
                            class="border border-red-300 text-red-700 hover:bg-red-50 px-4 py-2 rounded-lg font-medium transition-colors duration-200">
                        Delete Project
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Project Details -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                <h2 class="text-lg font-semibold text-slate-800 mb-4">Project Details</h2>
                
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-slate-500">Status:</span>
                        <span class="{{ $project->status_color }} font-medium">
                            {{ $project->status_display }}
                        </span>
                    </div>
                    
                    @if($project->isProcessing())
                        <div class="w-full bg-slate-200 rounded-full h-2">
                            <div class="bg-slate-600 h-2 rounded-full transition-all duration-300" 
                                 style="width: {{ $project->processing_progress }}%"></div>
                        </div>
                        <div class="text-xs text-slate-500 text-center">
                            {{ $project->processing_progress }}% complete
                            @if($project->estimated_completion)
                                • Est. completion: {{ $project->estimated_completion }}
                            @endif
                        </div>
                    @endif
                    
                    <div class="flex justify-between">
                        <span class="text-slate-500">File Type:</span>
                        <span class="text-slate-700">{{ strtoupper($project->file_type) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">File Size:</span>
                        <span class="text-slate-700">{{ $project->file_size_human }}</span>
                    </div>
                    
                    @if($project->hasPDF())
                        <div class="flex justify-between">
                            <span class="text-slate-500">PDF Size:</span>
                            <span class="text-slate-700">{{ $project->pdf_size_human }}</span>
                        </div>
                    @endif
                    
                    <div class="flex justify-between">
                        <span class="text-slate-500">Created:</span>
                        <span class="text-slate-700">{{ $project->created_at->format('M j, Y g:i A') }}</span>
                    </div>
                    @if($project->updated_at != $project->created_at)
                        <div class="flex justify-between">
                            <span class="text-slate-500">Updated:</span>
                            <span class="text-slate-700">{{ $project->updated_at->format('M j, Y g:i A') }}</span>
                        </div>
                    @endif
                    
                    @if($project->extracted_text)
                        <div class="pt-3 border-t border-slate-200">
                            <div class="flex justify-between">
                                <span class="text-slate-500">Original Words:</span>
                                <span class="text-slate-700">{{ number_format($project->extracted_text_word_count) }}</span>
                            </div>
                        </div>
                    @endif
                    
                    @if($project->cat_narrative)
                        <div class="flex justify-between">
                            <span class="text-slate-500">Cat Story Words:</span>
                            <span class="text-slate-700">{{ number_format($project->cat_narrative_word_count) }}</span>
                        </div>
                    @endif

                    @if($project->formatted_narrative)
                        <div class="flex justify-between">
                            <span class="text-slate-500">Formatted Words:</span>
                            <span class="text-slate-700">{{ number_format($project->formatted_narrative_word_count) }}</span>
                        </div>
                    @endif
                </div>
                
                <!-- PDF Quick Actions -->
                @if($project->hasPDF())
                    <div class="mt-6 pt-4 border-t border-slate-200">
                        <h3 class="text-sm font-medium text-slate-700 mb-3">📄 PDF Actions</h3>
                        <div class="space-y-2">
                            <a href="{{ route('projects.download-pdf', $project) }}" 
                               class="w-full bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-2 rounded-lg text-sm font-medium transition-colors duration-200 flex items-center justify-center">
                                <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                Download PDF
                            </a>
                            <a href="{{ route('projects.preview-pdf', $project) }}" 
                               target="_blank"
                               class="w-full border border-slate-300 text-slate-700 hover:bg-slate-50 px-3 py-2 rounded-lg text-sm font-medium transition-colors duration-200 flex items-center justify-center">
                                <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                                Preview PDF
                            </a>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- Content Preview -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                <h2 class="text-lg font-semibold text-slate-800 mb-4">Content Preview</h2>
                
                @if($project->status === 'failed')
                    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-red-400 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-red-800">Processing Failed</h3>
                                <div class="mt-1 text-sm text-red-700">
                                    {{ $project->error_message }}
                                </div>
                            </div>
                        </div>
                    </div>
                    
                @elseif($project->hasFormattedNarrative())
                    <!-- Formatted Story Display -->
                    <div class="prose prose-slate max-w-none">
                        <div class="flex items-center mb-6">
                            <span class="text-3xl mr-3">📖</span>
                            <div class="flex-1">
                                <h3 class="text-xl font-bold text-slate-800 mb-1">
                                    {{ $project->formatted_title ?: 'Your Cat Story' }}
                                </h3>
                                <p class="text-sm text-slate-500">
                                    {{ number_format($project->formatted_narrative_word_count) }} words
                                    • Estimated {{ ceil($project->formatted_narrative_word_count / 225) }} min read
                                </p>
                            </div>
                            <div class="flex flex-col items-end space-y-2">
                                @if($project->status === 'completed')
                                    <span class="text-sm bg-emerald-100 text-emerald-700 px-3 py-1 rounded-full">
                                        ✅ Complete
                                    </span>
                                    @if($project->hasPDF())
                                        <span class="text-xs bg-blue-100 text-blue-700 px-2 py-1 rounded-full">
                                            📄 PDF Ready
                                        </span>
                                    @endif
                                @elseif($project->status === 'generating_pdf')
                                    <span class="text-sm bg-amber-100 text-amber-700 px-3 py-1 rounded-full">
                                        🔄 Generating PDF
                                    </span>
                                @endif
                            </div>
                        </div>
                        
                        <!-- PDF Status Banner -->
                        @if($project->hasPDF())
                            <div class="mb-6 p-4 bg-gradient-to-r from-emerald-50 to-blue-50 border border-emerald-200 rounded-lg">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center">
                                        <div class="text-2xl mr-3">📄</div>
                                        <div>
                                            <h4 class="font-semibold text-emerald-800">PDF Ready for Download!</h4>
                                            <p class="text-sm text-emerald-600">
                                                Your beautifully formatted cat story is ready as a {{ $project->pdf_size_human }} PDF document.
                                            </p>
                                        </div>
                                    </div>
                                    <div class="flex space-x-2">
                                        <a href="{{ route('projects.download-pdf', $project) }}" 
                                           class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors duration-200">
                                            Download
                                        </a>
                                        <a href="{{ route('projects.preview-pdf', $project) }}" 
                                           target="_blank"
                                           class="border border-emerald-300 text-emerald-700 hover:bg-emerald-50 px-4 py-2 rounded-lg text-sm font-medium transition-colors duration-200">
                                            Preview
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @elseif($project->status === 'generating_pdf')
                            <div class="mb-6 p-4 bg-amber-50 border border-amber-200 rounded-lg">
                                <div class="flex items-center">
                                    <div class="animate-spin rounded-full h-5 w-5 border-b-2 border-amber-500 mr-3"></div>
                                    <div>
                                        <h4 class="font-medium text-amber-800">Generating PDF Document</h4>
                                        <p class="text-sm text-amber-600">
                                            Creating your beautifully formatted PDF... Estimated time: {{ $project->estimated_pdf_generation_time }} seconds
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @endif
                        
                        <div class="bg-gradient-to-br from-slate-50 to-slate-100 rounded-lg p-6 border border-slate-200">
                            @if(strlen($project->formatted_narrative) <= 1500)
                                <div class="formatted-story whitespace-pre-line">
                                    {!! nl2br(e($project->formatted_narrative)) !!}
                                </div>
                            @else
                                <div id="storyPreview" class="formatted-story whitespace-pre-line">
                                    {!! nl2br(e(Str::limit($project->formatted_narrative, 1500))) !!}
                                </div>
                                <div class="mt-4 pt-4 border-t border-slate-300">
                                    <div class="flex items-center justify-between">
                                        <span class="text-slate-500 text-sm">Showing first 1500 characters</span>
                                        <button onclick="toggleFullStory()" 
                                                class="text-slate-600 hover:text-slate-800 text-sm font-medium underline">
                                            Show Full Story
                                        </button>
                                    </div>
                                </div>
                                
                                <div id="fullStory" class="hidden mt-4 pt-4 border-t border-slate-300">
                                    <div class="formatted-story whitespace-pre-line">
                                        {!! nl2br(e(substr($project->formatted_narrative, 1500))) !!}
                                    </div>
                                    <div class="mt-4">
                                        <button onclick="toggleFullStory()" 
                                                class="text-slate-600 hover:text-slate-800 text-sm font-medium underline">
                                            Show Less
                                        </button>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                    
                @elseif($project->cat_narrative)
                    <!-- Raw Cat Narrative (before formatting) -->
                    <div class="prose prose-slate max-w-none">
                        <div class="flex items-center mb-4">
                            <span class="text-2xl mr-2">🐱</span>
                            <h3 class="text-lg font-medium text-slate-800 mb-0">Cat Narrative</h3>
                            @if($project->status === 'formatting')
                                <span class="ml-auto text-sm bg-amber-100 text-amber-700 px-2 py-1 rounded-full">
                                    🔄 Formatting into Story
                                </span>
                            @endif
                        </div>
                        
                        <div class="bg-gradient-to-br from-slate-50 to-slate-100 rounded-lg p-6 text-slate-700 leading-relaxed border border-slate-200">
                            @if(strlen($project->cat_narrative) <= 800)
                                {!! nl2br(e($project->cat_narrative)) !!}
                            @else
                                {!! nl2br(e(Str::limit($project->cat_narrative, 800))) !!}
                                <div class="mt-4 pt-4 border-t border-slate-300">
                                    <div class="flex items-center justify-between">
                                        <span class="text-slate-500 text-sm">Showing first 800 characters</span>
                                        <button onclick="toggleFullText()" 
                                                class="text-slate-600 hover:text-slate-800 text-sm font-medium underline">
                                            Show Full Story
                                        </button>
                                    </div>
                                </div>
                                
                                <div id="fullText" class="hidden mt-4 pt-4 border-t border-slate-300">
                                    {!! nl2br(e(substr($project->cat_narrative, 800))) !!}
                                    <div class="mt-4">
                                        <button onclick="toggleFullText()" 
                                                class="text-slate-600 hover:text-slate-800 text-sm font-medium underline">
                                            Show Less
                                        </button>
                                    </div>
                                </div>
                            @endif
                        </div>
                        
                        @if($project->status === 'formatting')
                            <div class="mt-4 p-3 bg-amber-50 border border-amber-200 rounded-lg">
                                <div class="flex items-center text-sm text-amber-700">
                                    <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-amber-500 mr-2"></div>
                                    Formatting into a structured story with chapters and styling...
                                </div>
                            </div>
                        @endif
                    </div>
                    
                @elseif($project->extracted_text)
                    <!-- Extracted Text Preview -->
                    <div class="prose prose-slate max-w-none">
                        <div class="flex items-center mb-4">
                            <span class="text-2xl mr-2">📄</span>
                            <h3 class="text-lg font-medium text-slate-800 mb-0">Extracted Text Preview</h3>
                        </div>
                        
                        <div class="bg-slate-50 rounded-lg p-4 text-slate-700 leading-relaxed border border-slate-200">
                            {{ Str::limit($project->extracted_text, 500) }}
                            @if(strlen($project->extracted_text) > 500)
                                <span class="text-slate-500">... (showing first 500 characters)</span>
                            @endif
                        </div>
                        
                        @if($project->status === 'converting_to_cat')
                            <div class="mt-4 p-3 bg-amber-50 border border-amber-200 rounded-lg">
                                <div class="flex items-center text-sm text-amber-700">
                                    <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-amber-500 mr-2"></div>
                                    Converting to cat narrative... This may take a few minutes.
                                </div>
                            </div>
                        @elseif($project->status === 'text_extracted')
                            <div class="mt-4 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                                <div class="flex items-center text-sm text-blue-700">
                                    <svg class="h-4 w-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    Text extraction complete. Cat narrative conversion will begin shortly.
                                </div>
                            </div>
                        @endif
                    </div>
                    
                @else
                    <!-- Processing Status -->
                    <div class="text-center py-12">
                        <div class="text-6xl mb-4">🐱</div>
                        <h3 class="text-lg font-medium text-slate-700 mb-2">
                            @if($project->status === 'uploaded')
                                Ready to Process
                            @else
                                {{ $project->status_display }}
                            @endif
                        </h3>
                        <p class="text-slate-500 mb-4">
                            @if($project->status === 'uploaded')
                                Processing will begin shortly...
                            @elseif($project->status === 'extracting_text')
                                Extracting text from your document...
                            @else
                                {{ $project->status_display }}...
                            @endif
                        </p>
                        
                        @if($project->isProcessing())
                            <div class="inline-flex items-center text-sm text-slate-500">
                                <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-slate-400 mr-2"></div>
                                This may take a few minutes depending on document size...
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@if(strlen($project->cat_narrative ?? '') > 800 || strlen($project->formatted_narrative ?? '') > 1500)
<script>
function toggleFullText() {
    const fullText = document.getElementById('fullText');
    if (fullText && fullText.classList.contains('hidden')) {
        fullText.classList.remove('hidden');
    } else if (fullText) {
        fullText.classList.add('hidden');
    }
}

function toggleFullStory() {
    const fullStory = document.getElementById('fullStory');
    if (fullStory && fullStory.classList.contains('hidden')) {
        fullStory.classList.remove('hidden');
    } else if (fullStory) {
        fullStory.classList.add('hidden');
    }
}
</script>
@endif

@if($project->isProcessing())
<script>
// Auto-refresh page every 10 seconds for processing status updates
setTimeout(function() {
    window.location.reload();
}, 10000);
</script>
@endif

<style>
.formatted-story {
    line-height: 1.7;
    font-family: 'Inter', system-ui, sans-serif;
}

.formatted-story h1 {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 1rem;
}

.formatted-story h2 {
    font-size: 1.25rem;
    font-weight: 600;
    color: #334155;
    margin-top: 2rem;
    margin-bottom: 1rem;
}

.formatted-story h3 {
    font-size: 1.125rem;
    font-weight: 600;
    color: #475569;
    margin-top: 1.5rem;
    margin-bottom: 0.75rem;
}
</style>
@endsection