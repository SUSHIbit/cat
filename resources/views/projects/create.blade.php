@extends('layouts.app')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-slate-800 mb-2">Upload Document</h1>
        <p class="text-slate-600">Upload a Word, PDF, or PowerPoint file to transform into a cat narrative</p>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-8">
        <form action="{{ route('projects.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf
            
            <!-- File Upload -->
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Document File</label>
                <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-slate-300 border-dashed rounded-lg hover:border-slate-400 transition-colors duration-200">
                    <div class="space-y-1 text-center">
                        <svg class="mx-auto h-12 w-12 text-slate-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                            <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        <div class="flex text-sm text-slate-600">
                            <label for="file" class="relative cursor-pointer bg-white rounded-md font-medium text-slate-700 hover:text-slate-500 focus-within:outline-none">
                                <span>Upload a file</span>
                                <input id="file" name="file" type="file" class="sr-only" accept=".docx,.pdf,.pptx" required>
                            </label>
                            <p class="pl-1">or drag and drop</p>
                        </div>
                        <p class="text-xs text-slate-500">
                            DOCX, PDF, PPTX up to 10MB
                        </p>
                    </div>
                </div>
                @error('file')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Title -->
            <div>
                <label for="title" class="block text-sm font-medium text-slate-700 mb-2">
                    Project Title <span class="text-slate-400">(optional)</span>
                </label>
                <input type="text" 
                       name="title" 
                       id="title" 
                       value="{{ old('title') }}"
                       class="block w-full px-3 py-2 border border-slate-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-slate-500 focus:border-slate-500"
                       placeholder="Leave blank to use filename">
                @error('title')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Submit Button -->
            <div class="flex justify-end space-x-3">
                <a href="{{ route('projects.index') }}" 
                   class="px-4 py-2 border border-slate-300 rounded-lg text-slate-700 hover:bg-slate-50 transition-colors duration-200">
                    Cancel
                </a>
                <button type="submit" 
                        class="bg-slate-700 hover:bg-slate-800 text-white px-6 py-2 rounded-lg font-medium transition-colors duration-200">
                    Upload & Process
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Simple drag and drop functionality
document.addEventListener('DOMContentLoaded', function() {
    const dropArea = document.querySelector('.border-dashed');
    const fileInput = document.getElementById('file');
    
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropArea.addEventListener(eventName, preventDefaults, false);
    });
    
    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }
    
    ['dragenter', 'dragover'].forEach(eventName => {
        dropArea.addEventListener(eventName, highlight, false);
    });
    
    ['dragleave', 'drop'].forEach(eventName => {
        dropArea.addEventListener(eventName, unhighlight, false);
    });
    
    function highlight(e) {
        dropArea.classList.add('border-slate-500', 'bg-slate-50');
    }
    
    function unhighlight(e) {
        dropArea.classList.remove('border-slate-500', 'bg-slate-50');
    }
    
    dropArea.addEventListener('drop', handleDrop, false);
    
    function handleDrop(e) {
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            fileInput.files = files;
        }
    }
});
</script>
@endsection