<?php

namespace App\Jobs;

use App\Models\Project;
use App\Services\PDFGenerator;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessPDFGeneration implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutes timeout
    public $tries = 2; // Allow 2 retry attempts

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Project $project
    ) {}

    /**
     * Execute the job.
     */
    public function handle(PDFGenerator $generator): void
    {
        try {
            Log::info("Starting PDF generation for project: {$this->project->id}");
            
            // Validate project status
            if ($this->project->status !== 'generating_pdf') {
                throw new Exception("Project is not ready for PDF generation. Current status: {$this->project->status}");
            }
            
            // Validate generation requirements
            $validationIssues = $generator->validateGenerationRequirements($this->project);
            if (!empty($validationIssues)) {
                throw new Exception("PDF generation validation failed: " . implode(', ', $validationIssues));
            }
            
            // Generate PDF
            Log::info("Generating PDF document", [
                'project_id' => $this->project->id,
                'narrative_length' => strlen($this->project->formatted_narrative),
                'estimated_time' => $generator->estimateGenerationTime($this->project)
            ]);
            
            $pdfPath = $generator->generateCatNarrativePDF($this->project);
            
            // Validate generated PDF
            if (empty($pdfPath)) {
                throw new Exception("PDF generation returned empty file path");
            }
            
            // Update project with PDF path
            $this->project->update([
                'pdf_path' => $pdfPath,
                'status' => 'completed'
            ]);
            
            Log::info("PDF generation completed for project: {$this->project->id}", [
                'pdf_path' => $pdfPath,
                'estimated_size' => $generator->estimatePDFSize($this->project)
            ]);
            
        } catch (Exception $e) {
            Log::error("PDF generation failed for project: {$this->project->id}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Update project status to failed
            $this->project->update([
                'status' => 'failed',
                'error_message' => 'PDF generation failed: ' . $e->getMessage()
            ]);
            
            // Re-throw to trigger job failure
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(Exception $exception): void
    {
        Log::error("PDF generation job failed permanently for project: {$this->project->id}", [
            'error' => $exception->getMessage()
        ]);
        
        $this->project->update([
            'status' => 'failed',
            'error_message' => 'PDF generation failed after multiple attempts: ' . $exception->getMessage()
        ]);
    }

    /**
     * Calculate the number of seconds the job can run before timing out.
     */
    public function retryUntil(): \DateTime
    {
        return now()->addMinutes(15);
    }
}