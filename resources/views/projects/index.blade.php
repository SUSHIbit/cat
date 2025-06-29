@extends('layouts.app')

@section('content')
<div class="mb-8">
    <h1 class="text-3xl font-bold text-slate-800 mb-2">Your Cat Narratives</h1>
    <p class="text-slate-600">Upload documents to transform them into entertaining cat stories</p>
</div>

@if($projects->count() > 0)
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($projects as $project)
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 hover:shadow-md transition-shadow duration-200">
                <div class="flex items-start justify-between mb-4">
                    <h3 class="text-lg font-semibold text-slate-800 truncate pr-2">
                        @if($project->formatted_title)
                            {{ $project->formatted_title }}
                        @else
                            {{ $project->title }}
                        @endif
                    </h3>
                    <span class="text-xs px-2 py-1 rounded-full bg-slate-100 text-slate-600 flex-shrink-0">
                        {{ strtoupper($project->file_type) }}
                    </span>
                </div>

                <div class="space-y-2 mb-4">
                    <div class="flex justify-between text-sm">
                        <span class="text-slate-500">Status:</span>
                        <span class="{{ $project->status_color }} font-medium">
                            {{ $project->status_display }}
                        </span>
                    </div>
                    
                    @if($project->isProcessing())
                        <div class="w-full bg-slate-200 rounded-full h-1.5">
                            <div class="bg-slate-600 h-1.5 rounded-full transition-all duration-300" 
                                 style="width: {{ $project->processing_progress }}%"></div>
                        </div>
                        <div class="text-xs text-slate-500 text-center">
                            {{ $project->processing_progress }}% complete
                        </div>
                    @endif
                    
                    <div class="flex justify-between text-sm">
                        <span class="text-slate-500">Size:</span>
                        <span class="text-slate-700">{{ $project->file_size_human }}</span>
                    </div>
                    
                    @if($project->formatted_narrative_word_count > 0)
                        <div class="flex justify-between text-sm">
                            <span class="text-slate-500">Story Length:</span>
                            <span class="text-slate-700">{{ number_format($project->formatted_narrative_word_count) }} words</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-slate-500">Reading Time:</span>
                            <span class="text-slate-700">{{ ceil($project->formatted_narrative_word_count / 225) }} min</span>
                        </div>
                    @elseif($project->cat_narrative_word_count > 0)
                        <div class="flex justify-between text-sm">
                            <span class="text-slate-500">Cat Story:</span>
                            <span class="text-slate-700">{{ number_format($project->cat_narrative_word_count) }} words</span>
                        </div>
                    @elseif($project->extracted_text_word_count > 0)
                        <div class="flex justify-between text-sm">
                            <span class="text-slate-500">Original Text:</span>
                            <span class="text-slate-700">{{ number_format($project->extracted_text_word_count) }} words</span>
                        </div>
                    @endif
                    
                    <div class="flex justify-between text-sm">
                        <span class="text-slate-500">Created:</span>
                        <span class="text-slate-700">{{ $project->created_at->format('M j, Y') }}</span>
                    </div>
                </div>

                <!-- Preview Content -->
                @if($project->hasFormattedNarrative())
                    <div class="mb-4 p-3 bg-gradient-to-br from-slate-50 to-slate-100 rounded-lg border border-slate-200">
                        <div class="flex items-center mb-2">
                            <span class="text-sm mr-1">📖</span>
                            <span class="text-xs font-medium text-slate-600">Formatted Story Preview</span>
                        </div>
                        <p class="text-xs text-slate-600 leading-relaxed">
                            {{ Str::limit($project->formatted_narrative_preview, 120) }}
                        </p>
                    </div>
                @elseif($project->cat_narrative && strlen($project->cat_narrative) > 50)
                    <div class="mb-4 p-3 bg-gradient-to-br from-orange-50 to-orange-100 rounded-lg border border-orange-200">
                        <div class="flex items-center mb-2">
                            <span class="text-sm mr-1">🐱</span>
                            <span class="text-xs font-medium text-orange-600">Cat Narrative Preview</span>
                        </div>
                        <p class="text-xs text-orange-700 leading-relaxed">
                            {{ Str::limit($project->cat_narrative, 120) }}
                        </p>
                    </div>
                @elseif($project->extracted_text && strlen($project->extracted_text) > 50)
                    <div class="mb-4 p-3 bg-slate-50 rounded-lg border border-slate-200">
                        <div class="flex items-center mb-2">
                            <span class="text-sm mr-1">📄</span>
                            <span class="text-xs font-medium text-slate-500">Original Text Preview</span>
                        </div>
                        <p class="text-xs text-slate-600 leading-relaxed">
                            {{ Str::limit($project->extracted_text, 120) }}
                        </p>
                    </div>
                @endif

                <!-- Status Messages -->
                @if($project->status === 'failed')
                    <div class="mb-4 p-2 bg-red-50 border border-red-200 rounded-lg">
                        <div class="flex items-center text-xs text-red-700">
                            <svg class="h-3 w-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                            </svg>
                            Processing failed
                        </div>
                    </div>
                @elseif($project->isProcessing())
                    <div class="mb-4 p-2 bg-amber-50 border border-amber-200 rounded-lg">
                        <div class="flex items-center text-xs text-amber-700">
                            <div class="animate-spin rounded-full h-3 w-3 border-b border-amber-500 mr-2"></div>
                            @if($project->estimated_completion)
                                Est. completion: {{ $project->estimated_completion }}
                            @else
                                Processing...
                            @endif
                        </div>
                    </div>
                @elseif($project->status === 'completed')
                    <div class="mb-4 p-2 bg-emerald-50 border border-emerald-200 rounded-lg">
                        <div class="flex items-center text-xs text-emerald-700">
                            <svg class="h-3 w-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            Ready for download
                        </div>
                    </div>
                @endif

                <!-- Action Buttons -->
                <div class="flex space-x-2">
                    <a href="{{ route('projects.show', $project) }}" 
                       class="flex-1 bg-slate-100 hover:bg-slate-200 text-slate-700 px-3 py-2 rounded-lg text-sm font-medium text-center transition-colors duration-200">
                        @if($project->isProcessing())
                            View Progress
                        @else
                            View Details
                        @endif
                    </a>
                    @if($project->status === 'completed' && $project->pdf_path)
                        <a href="#" 
                           class="bg-slate-700 hover:bg-slate-800 text-white px-3 py-2 rounded-lg text-sm font-medium transition-colors duration-200">
                            Download PDF
                        </a>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    <div class="mt-8">
        {{ $projects->links() }}
    </div>
@else
    <div class="text-center py-16">
        <div class="text-6xl mb-4">🐱</div>
        <h2 class="text-xl font-semibold text-slate-700 mb-2">No cat narratives yet</h2>
        <p class="text-slate-500 mb-6">Upload your first document to get started!</p>
        <a href="{{ route('projects.create') }}" 
           class="bg-slate-700 hover:bg-slate-800 text-white px-6 py-3 rounded-lg font-medium transition-colors duration-200">
            Upload Document
        </a>
    </div>
@endif

@php
    $hasProcessingProjects = false;
    foreach($projects as $project) {
        if($project->isProcessing()) {
            $hasProcessingProjects = true;
            break;
        }
    }
@endphp

@if($hasProcessingProjects)
<script>
// Auto-refresh page every 15 seconds when there are processing projects
setTimeout(function() {
    window.location.reload();
}, 15000);
</script>
@endif
@endsection