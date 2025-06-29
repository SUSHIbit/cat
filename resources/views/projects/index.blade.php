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
                        {{ $project->title }}
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
                    <div class="flex justify-between text-sm">
                        <span class="text-slate-500">Size:</span>
                        <span class="text-slate-700">{{ $project->file_size_human }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-slate-500">Created:</span>
                        <span class="text-slate-700">{{ $project->created_at->format('M j, Y') }}</span>
                    </div>
                </div>

                <div class="flex space-x-2">
                    <a href="{{ route('projects.show', $project) }}" 
                       class="flex-1 bg-slate-100 hover:bg-slate-200 text-slate-700 px-3 py-2 rounded-lg text-sm font-medium text-center transition-colors duration-200">
                        View Details
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
@endsection