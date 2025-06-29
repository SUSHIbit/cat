<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Jobs\ProcessDocumentTextExtraction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProjectController extends Controller
{
    public function index()
    {
        $projects = Project::latest()->paginate(12);
        return view('projects.index', compact('projects'));
    }

    public function create()
    {
        return view('projects.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:docx,pdf,pptx|max:10240', // 10MB max
            'title' => 'nullable|string|max:255',
        ]);

        $file = $request->file('file');
        $originalName = $file->getClientOriginalName();
        $fileName = time() . '_' . Str::slug(pathinfo($originalName, PATHINFO_FILENAME)) . '.' . $file->getClientOriginalExtension();
        
        // Store file in storage/app/uploads
        $filePath = $file->storeAs('uploads', $fileName);

        $project = Project::create([
            'title' => $request->title ?: pathinfo($originalName, PATHINFO_FILENAME),
            'original_filename' => $originalName,
            'file_path' => $filePath,
            'file_type' => $file->getClientOriginalExtension(),
            'file_size' => $file->getSize(),
            'status' => 'uploaded',
        ]);

        // Dispatch text extraction job
        ProcessDocumentTextExtraction::dispatch($project);

        return redirect()->route('projects.show', $project)
            ->with('success', 'Document uploaded successfully! Text extraction is starting...');
    }

    public function show(Project $project)
    {
        return view('projects.show', compact('project'));
    }

    public function destroy(Project $project)
    {
        // Delete associated files
        if ($project->file_path && Storage::exists($project->file_path)) {
            Storage::delete($project->file_path);
        }
        
        if ($project->pdf_path && Storage::exists($project->pdf_path)) {
            Storage::delete($project->pdf_path);
        }

        $project->delete();

        return redirect()->route('projects.index')
            ->with('success', 'Project deleted successfully.');
    }
}