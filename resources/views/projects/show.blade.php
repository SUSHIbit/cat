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
                @if($project->status === 'completed' && $project->pdf_path)
                    <a href="#" 
                       class="bg-slate-700 hover:bg-slate-800 text-white px-4 py-2 rounded-lg font-medium transition-colors duration-200">
                        Download PDF
                    </a>
                @endif
                <form action="{{ route('projects.destroy', $project) }}" method="POST" class="inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" 
                            onclick="return confirm('Are you sure you want to delete this project?')"
                            class="border border-red-300 text-red-700 hover:bg-red-50 px-4 py-2 rounded-lg font-medium transition-colors duration-200">
                        Delete
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
                    <div class="flex justify-between">
                        <span class="text-slate-500">File Type:</span>
                        <span class="text-slate-700">{{ strtoupper($project->file_type) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">File Size:</span>
                        <span class="text-slate-700">{{ $project->file_size_human }}</span>
                    </div>
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
                                <span class="text-slate-500">Word Count:</span>
                                <span class="text-slate-700">{{ str_word_count($project->extracted_text) }}</span>
                            </div>
                        </div>
                    @endif
                    
                    @if($project->cat_narrative)
                        <div class="flex justify-between">
                            <span class="text-slate-500">Cat Story Length:</span>
                            <span class="text-slate-700">{{ str_word_count($project->cat_narrative) }} words</span>
                        </div>
                    @endif
                </div>
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
                    
                @elseif($project->cat_narrative)
                    <div class="prose prose-slate max-w-none">
                        <div class="flex items-center mb-4">
                            <span class="text-2xl mr-2">🐱</span>
                            <h3 class="text-lg font-medium text-slate-800 mb-0">Cat Narrative</h3>
                            @if($project->status === 'completed')
                                <span class="ml-auto text-sm bg-emerald-100 text-emerald-700 px-2 py-1 rounded-full">
                                    ✅ Complete
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
                        
                        @if($project->status === 'completed')
                            <div class="mt-4 p-3 bg-emerald-50 border border-emerald-200 rounded-lg">
                                <div class="flex items-center text-sm text-emerald-700">
                                    <svg class="h-4 w-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    Cat narrative generation complete! Ready for PDF download.
                                </div>
                            </div>
                        @endif
                    </div>
                    
                @elseif($project->extracted_text)
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
                        
                        @if(in_array($project->status, ['extracting_text', 'converting_to_cat', 'formatting', 'generating_pdf']))
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

@if(strlen($project->cat_narrative ?? '') > 800)
<script>
function toggleFullText() {
    const fullText = document.getElementById('fullText');
    if (fullText.classList.contains('hidden')) {
        fullText.classList.remove('hidden');
    } else {
        fullText.classList.add('hidden');
    }
}
</script>
@endif

@if(in_array($project->status, ['extracting_text', 'converting_to_cat', 'formatting', 'generating_pdf']))
<script>
// Auto-refresh page every 10 seconds for processing status updates
setTimeout(function() {
    window.location.reload();
}, 10000);
</script>
@endif
@endsection