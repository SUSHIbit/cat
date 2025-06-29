<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Jobs\ProcessDocumentTextExtraction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

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

    /**
     * Download the generated PDF
     */
    public function downloadPDF(Project $project)
    {
        // Check if project has completed PDF generation
        if ($project->status !== 'completed' || !$project->pdf_path) {
            return redirect()->route('projects.show', $project)
                ->with('error', 'PDF is not ready for download yet.');
        }

        // Check if PDF file exists
        if (!Storage::exists($project->pdf_path)) {
            return redirect()->route('projects.show', $project)
                ->with('error', 'PDF file not found. Please try regenerating the document.');
        }

        // Generate download filename
        $title = $this->sanitizeFilename($project->formatted_title ?? $project->title);
        $downloadFilename = $title . '_cat_narrative.pdf';

        // Return file download response
        return Storage::download($project->pdf_path, $downloadFilename, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $downloadFilename . '"'
        ]);
    }

    /**
     * Preview the PDF in browser
     */
    public function previewPDF(Project $project)
    {
        // Check if project has completed PDF generation
        if ($project->status !== 'completed' || !$project->pdf_path) {
            abort(404, 'PDF not found or not ready');
        }

        // Check if PDF file exists
        if (!Storage::exists($project->pdf_path)) {
            abort(404, 'PDF file not found');
        }

        // Return file for inline viewing
        return response(Storage::get($project->pdf_path), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="preview.pdf"'
        ]);
    }

    /**
     * Regenerate PDF for a project
     */
    public function regeneratePDF(Project $project)
    {
        // Check if project has formatted narrative
        if (empty($project->formatted_narrative)) {
            return redirect()->route('projects.show', $project)
                ->with('error', 'Cannot regenerate PDF: no formatted narrative available.');
        }

        // Check if project is not currently processing
        if ($project->isProcessing()) {
            return redirect()->route('projects.show', $project)
                ->with('error', 'Cannot regenerate PDF: project is currently being processed.');
        }

        // Delete old PDF if exists
        if ($project->pdf_path && Storage::exists($project->pdf_path)) {
            Storage::delete($project->pdf_path);
        }

        // Update status and dispatch PDF generation
        $project->update([
            'status' => 'generating_pdf',
            'pdf_path' => null,
            'error_message' => null
        ]);

        \App\Jobs\ProcessPDFGeneration::dispatch($project);

        return redirect()->route('projects.show', $project)
            ->with('success', 'PDF regeneration started. Please wait a moment...');
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

    /**
     * Sanitize filename for download
     */
    private function sanitizeFilename(string $filename): string
    {
        // Remove or replace problematic characters
        $filename = preg_replace('/[^a-zA-Z0-9_\-\s]/', '', $filename);
        $filename = preg_replace('/\s+/', '_', $filename);
        $filename = trim($filename, '_');
        
        // Ensure filename isn't empty
        if (empty($filename)) {
            $filename = 'cat_narrative';
        }
        
        // Limit length
        return Str::limit($filename, 50, '');
    }
}