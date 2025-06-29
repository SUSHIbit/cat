<?php

namespace App\Jobs;

use App\Models\Project;
use App\Services\TextFormatter;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessTextFormatting implements ShouldQueue
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
    public function handle(TextFormatter $formatter): void
    {
        try {
            Log::info("Starting text formatting for project: {$this->project->id}");
            
            // Validate project status
            if ($this->project->status !== 'converting_to_cat') {
                throw new Exception("Project is not ready for text formatting. Current status: {$this->project->status}");
            }
            
            // Validate cat narrative exists
            if (empty($this->project->cat_narrative)) {
                throw new Exception("No cat narrative found for formatting");
            }
            
            // Update status to formatting
            $this->project->update(['status' => 'formatting']);
            
            // Format the cat narrative into structured story
            Log::info("Formatting cat narrative into structured story", [
                'project_id' => $this->project->id,
                'narrative_length' => strlen($this->project->cat_narrative),
                'word_count' => str_word_count($this->project->cat_narrative)
            ]);
            
            $formattedData = $formatter->formatNarrative(
                $this->project->cat_narrative, 
                $this->project->title
            );
            
            // Validate the formatted structure
            $validationIssues = $formatter->validateFormattedNarrative($formattedData);
            if (!empty($validationIssues)) {
                throw new Exception("Formatted narrative validation failed: " . implode(', ', $validationIssues));
            }
            
            // Generate the complete formatted story string
            $formattedStory = $formatter->generateFormattedStory($formattedData);
            
            // Validate the final formatted story
            if (empty(trim($formattedStory))) {
                throw new Exception("Generated formatted story is empty");
            }
            
            if (strlen($formattedStory) < 100) {
                throw new Exception("Generated formatted story is too short");
            }
            
            // Save the formatted narrative and metadata
            $this->project->update([
                'formatted_narrative' => $formattedStory,
                'status' => 'generating_pdf'
            ]);
            
            Log::info("Text formatting completed for project: {$this->project->id}", [
                'formatted_length' => strlen($formattedStory),
                'chapter_count' => count($formattedData['chapters']),
                'total_word_count' => $formattedData['total_word_count'],
                'estimated_reading_time' => $formattedData['estimated_reading_time']
            ]);
            
            // Dispatch next phase job (PDF generation - will be created in Phase 5)
            // ProcessPDFGeneration::dispatch($this->project);
            
            // For now, let's update status to indicate completion of this phase
            $this->project->update(['status' => 'completed']);
            
        } catch (Exception $e) {
            Log::error("Text formatting failed for project: {$this->project->id}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Update project status to failed
            $this->project->update([
                'status' => 'failed',
                'error_message' => 'Text formatting failed: ' . $e->getMessage()
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
        Log::error("Text formatting job failed permanently for project: {$this->project->id}", [
            'error' => $exception->getMessage()
        ]);
        
        $this->project->update([
            'status' => 'failed',
            'error_message' => 'Text formatting failed after multiple attempts: ' . $exception->getMessage()
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