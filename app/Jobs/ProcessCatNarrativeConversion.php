<?php

namespace App\Jobs;

use App\Models\Project;
use App\Services\CatNarrativeConverter;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessCatNarrativeConversion implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600; // 10 minutes timeout for OpenAI processing
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
    public function handle(CatNarrativeConverter $converter): void
    {
        try {
            Log::info("Starting cat narrative conversion for project: {$this->project->id}");
            
            // Validate project status
            if ($this->project->status !== 'text_extracted') {
                throw new Exception("Project is not ready for cat narrative conversion. Current status: {$this->project->status}");
            }
            
            // Validate extracted text exists
            if (empty($this->project->extracted_text)) {
                throw new Exception("No extracted text found for conversion");
            }
            
            // Update status to converting
            $this->project->update(['status' => 'converting_to_cat']);
            
            // Validate OpenAI configuration
            if (!$converter->validateConfiguration()) {
                throw new Exception("OpenAI configuration is invalid or API is not accessible");
            }
            
            // Convert text to cat narrative
            Log::info("Converting text to cat narrative", [
                'project_id' => $this->project->id,
                'text_length' => strlen($this->project->extracted_text),
                'estimated_time' => $converter->estimateProcessingTime($this->project->extracted_text)
            ]);
            
            $catNarrative = $converter->convertToCatNarrative($this->project->extracted_text);
            
            // Validate the generated narrative
            if (empty(trim($catNarrative))) {
                throw new Exception("Generated cat narrative is empty");
            }
            
            if (strlen($catNarrative) < 50) {
                throw new Exception("Generated cat narrative is too short");
            }
            
            // Save the cat narrative
            $this->project->update([
                'cat_narrative' => $catNarrative,
                'status' => 'converting_to_cat' // Keep status for formatting job to pick up
            ]);
            
            Log::info("Cat narrative conversion completed for project: {$this->project->id}", [
                'narrative_length' => strlen($catNarrative),
                'word_count' => str_word_count($catNarrative)
            ]);
            
            // Dispatch next phase job (text formatting)
            ProcessTextFormatting::dispatch($this->project);
            
        } catch (Exception $e) {
            Log::error("Cat narrative conversion failed for project: {$this->project->id}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Update project status to failed
            $this->project->update([
                'status' => 'failed',
                'error_message' => 'Cat narrative conversion failed: ' . $e->getMessage()
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
        Log::error("Cat narrative conversion job failed permanently for project: {$this->project->id}", [
            'error' => $exception->getMessage()
        ]);
        
        $this->project->update([
            'status' => 'failed',
            'error_message' => 'Cat narrative conversion failed after multiple attempts: ' . $exception->getMessage()
        ]);
    }

    /**
     * Calculate the number of seconds the job can run before timing out.
     */
    public function retryUntil(): \DateTime
    {
        return now()->addMinutes(30);
    }
}