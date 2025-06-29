<?php

namespace App\Jobs;

use App\Models\Project;
use App\Services\DocumentParser;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessDocumentTextExtraction implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutes timeout
    public $tries = 3; // Allow 3 retry attempts

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Project $project
    ) {}

    /**
     * Execute the job.
     */
    public function handle(DocumentParser $parser): void
    {
        try {
            Log::info("Starting text extraction for project: {$this->project->id}");
            
            // Update status to extracting
            $this->project->update(['status' => 'extracting_text']);
            
            // Validate file type
            if (!$parser->canProcess($this->project->file_type)) {
                throw new Exception("Unsupported file type: {$this->project->file_type}");
            }
            
            // Extract text from document
            $extractedText = $parser->extractText(
                $this->project->file_path,
                $this->project->file_type
            );
            
            // Validate extracted text
            if (empty(trim($extractedText))) {
                throw new Exception("No text could be extracted from the document");
            }
            
            if (strlen($extractedText) < 10) {
                throw new Exception("Extracted text is too short (less than 10 characters)");
            }
            
            // Save extracted text and update status
            $this->project->update([
                'extracted_text' => $extractedText,
                'status' => 'text_extracted'
            ]);
            
            Log::info("Text extraction completed for project: {$this->project->id}", [
                'text_length' => strlen($extractedText),
                'word_count' => str_word_count($extractedText)
            ]);
            
            // Dispatch next phase job (this will be created in Phase 3)
            // ProcessCatNarrativeConversion::dispatch($this->project);
            
        } catch (Exception $e) {
            Log::error("Text extraction failed for project: {$this->project->id}", [
                'error' => $e->getMessage(),
                'file_path' => $this->project->file_path,
                'file_type' => $this->project->file_type
            ]);
            
            // Update project status to failed
            $this->project->update([
                'status' => 'failed',
                'error_message' => 'Text extraction failed: ' . $e->getMessage()
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
        Log::error("Text extraction job failed permanently for project: {$this->project->id}", [
            'error' => $exception->getMessage()
        ]);
        
        $this->project->update([
            'status' => 'failed',
            'error_message' => 'Text extraction failed after multiple attempts: ' . $exception->getMessage()
        ]);
    }
}