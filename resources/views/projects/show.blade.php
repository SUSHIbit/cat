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
                </div>
            </div>
        </div>

        <!-- Content Preview -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                <h2 class="text-lg font-semibold text-slate-800 mb-4">Content Preview</h2>
                
                @if($project->status === 'failed')
                    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
                        <strong>Processing Failed:</strong> {{ $project->error_message }}
                    </div>
                @elseif($project->cat_narrative)
                    <div class="prose prose-slate max-w-none">
                        <h3 class="text-lg font-medium text-slate-800 mb-3">Cat Narrative Preview</h3>
                        <div class="bg-slate-50 rounded-lg p-4 text-slate-700 leading-relaxed">
                            {{ Str::limit($project->cat_narrative, 500) }}
                            @if(strlen($project->cat_narrative) > 500)
                                <span class="text-slate-500">... (truncated)</span>
                            @endif
                        </div>
                    </div>
                @elseif($project->extracted_text)
                    <div class="prose prose-slate max-w-none">
                        <h3 class="text-lg font-medium text-slate-800 mb-3">Extracted Text Preview</h3>
                        <div class="bg-slate-50 rounded-lg p-4 text-slate-700 leading-relaxed">
                            {{ Str::limit($project->extracted_text, 500) }}
                            @if(strlen($project->extracted_text) > 500)
                                <span class="text-slate-500">... (truncated)</span>
                            @endif
                        </div>
                    </div>
                @else
                    <div class="text-center py-8">
                        <div class="text-4xl mb-4">🐱</div>
                        <p class="text-slate-500">
                            @if($project->status === 'uploaded')
                                Processing will begin shortly...
                            @else
                                {{ $project->status_display }}...
                            @endif
                        </p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection