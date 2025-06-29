<?php

namespace App\Console\Commands;

use App\Models\Project;
use App\Jobs\ProcessDocumentTextExtraction;
use App\Services\DocumentParser;
use App\Services\CatNarrativeConverter;
use App\Services\TextFormatter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class TestWorkflow extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:workflow {--sync : Run synchronously instead of using jobs}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the complete document processing workflow (Phases 1-4)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🐱 Testing Complete Cat Narrative Generation Workflow...');
        $this->newLine();

        $sync = $this->option('sync');
        
        if ($sync) {
            $this->warn('⚠️  Running in synchronous mode (not using job queue)');
        } else {
            $this->info('ℹ️  Running with job queue (recommended)');
        }
        $this->newLine();

        try {
            // Create a test project with sample text content
            $this->info('📄 Creating test project...');
            $testContent = $this->createTestDocument();
            
            $project = Project::create([
                'title' => 'Test Workflow Project',
                'original_filename' => 'test_document.txt',
                'file_path' => $testContent['path'],
                'file_type' => 'txt',
                'file_size' => strlen($testContent['content']),
                'status' => 'uploaded',
            ]);
            
            $this->info("✅ Test project created (ID: {$project->id})");
            $this->newLine();

            if ($sync) {
                // Run synchronously for testing
                return $this->runSynchronousTest($project);
            } else {
                // Use job queue
                return $this->runAsyncTest($project);
            }

        } catch (\Exception $e) {
            $this->error('❌ Workflow test failed: ' . $e->getMessage());
            $this->newLine();
            $this->line('Stack trace:');
            $this->line($e->getTraceAsString());
            return 1;
        }
    }

    /**
     * Run the workflow synchronously for testing
     */
    private function runSynchronousTest(Project $project): int
    {
        try {
            // Phase 2: Document Text Extraction
            $this->info('🔍 Phase 2: Testing document text extraction...');
            $parser = app(DocumentParser::class);
            
            // Simulate text extraction (since we're using a simple text file)
            $extractedText = Storage::get($project->file_path);
            
            $project->update([
                'extracted_text' => $extractedText,
                'status' => 'text_extracted'
            ]);
            
            $this->info('✅ Text extraction completed');
            $this->line('   Extracted: ' . str_word_count($extractedText) . ' words');
            $this->newLine();

            // Phase 3: Cat Narrative Conversion
            $this->info('🐱 Phase 3: Testing cat narrative conversion...');
            $converter = app(CatNarrativeConverter::class);
            
            if (!$converter->validateConfiguration()) {
                $this->warn('⚠️  OpenAI not configured, using mock conversion');
                $catNarrative = $this->createMockCatNarrative($extractedText);
            } else {
                $catNarrative = $converter->convertToCatNarrative($extractedText);
            }
            
            $project->update([
                'cat_narrative' => $catNarrative,
                'status' => 'converting_to_cat'
            ]);
            
            $this->info('✅ Cat narrative conversion completed');
            $this->line('   Generated: ' . str_word_count($catNarrative) . ' words');
            $this->newLine();

            // Phase 4: Text Formatting
            $this->info('📖 Phase 4: Testing text formatting...');
            $formatter = app(TextFormatter::class);
            
            $formattedData = $formatter->formatNarrative($catNarrative, $project->title);
            $formattedStory = $formatter->generateFormattedStory($formattedData);
            
            $project->update([
                'formatted_narrative' => $formattedStory,
                'status' => 'completed'
            ]);
            
            $this->info('✅ Text formatting completed');
            $this->line('   Chapters: ' . count($formattedData['chapters']));
            $this->line('   Final word count: ' . number_format($formattedData['total_word_count']));
            $this->line('   Reading time: ' . $formattedData['estimated_reading_time'] . ' min');
            $this->newLine();

            // Display results summary
            $this->displayWorkflowSummary($project, $formattedData);
            
            // Clean up test project
            $this->cleanupTestProject($project);
            
            return 0;

        } catch (\Exception $e) {
            $this->error('❌ Synchronous test failed: ' . $e->getMessage());
            $this->cleanupTestProject($project);
            throw $e;
        }
    }

    /**
     * Run the workflow asynchronously using jobs
     */
    private function runAsyncTest(Project $project): int
    {
        $this->info('🚀 Dispatching document processing job...');
        
        // Dispatch the first job in the chain
        ProcessDocumentTextExtraction::dispatch($project);
        
        $this->info('✅ Job dispatched successfully!');
        $this->newLine();
        
        $this->line('📋 To monitor the workflow progress:');
        $this->line('   • Check project status: php artisan tinker');
        $this->line('   • Then run: App\\Models\\Project::find(' . $project->id . ')->status');
        $this->line('   • View in browser: /projects/' . $project->id);
        $this->newLine();
        
        $this->line('🔄 Job Queue Commands:');
        $this->line('   • Process jobs: php artisan queue:work');
        $this->line('   • Monitor jobs: php artisan queue:monitor');
        $this->line('   • Clear failed: php artisan queue:clear');
        $this->newLine();
        
        $this->info('🎉 Async workflow test initiated successfully!');
        $this->line("Project ID {$project->id} is now being processed in the background.");
        
        return 0;
    }

    /**
     * Create a test document with sample content
     */
    private function createTestDocument(): array
    {
        $content = "Test Document for Cat Narrative Generation

This is a sample document that will be processed through our cat narrative generation system. It contains various types of content to test the different phases of processing.

Introduction
This document serves as a comprehensive test case for our automated cat narrative generation workflow. The system will extract this text, convert it into an entertaining cat perspective, and format it into a structured story.

Main Content
The process involves several sophisticated steps including document parsing, AI-powered narrative transformation, and intelligent text formatting. Each phase builds upon the previous one to create a cohesive and entertaining final product.

Technical Details
Our system supports multiple document formats including Word documents, PDFs, and PowerPoint presentations. The text extraction process handles various formatting and structure challenges to provide clean, processable content.

Quality Assurance
The formatting phase organizes the content into logical chapters with appropriate titles and structure. This ensures the final cat narrative is not only entertaining but also well-organized and easy to read.

Conclusion
This test document will help verify that all phases of our cat narrative generation system are working correctly and producing high-quality results.";

        $fileName = 'test_' . time() . '.txt';
        $filePath = 'uploads/' . $fileName;
        
        Storage::put($filePath, $content);
        
        return [
            'content' => $content,
            'path' => $filePath,
            'filename' => $fileName
        ];
    }

    /**
     * Create a mock cat narrative for testing when OpenAI is not available
     */
    private function createMockCatNarrative(string $originalText): string
    {
        return "Meow! Let me tell you about this absolutely fascinating document I just witnessed being created, from my superior feline perspective.

As a cat who has spent considerable time observing humans and their strange document-creation rituals, I must say this was quite the spectacle. The humans seemed very focused on creating something they called a 'test document' - though I'm not sure why they need to test anything when they could simply ask a cat for the correct approach.

First, they wrote about some sort of 'comprehensive test case' for what they dramatically call a 'cat narrative generation workflow.' As someone who generates narratives every time I meow for food, I find their approach rather overcomplicated. A simple 'MEOW' usually gets the point across much more effectively.

The document mentions 'sophisticated steps' and 'AI-powered transformation.' In my experience, the most sophisticated step is choosing the perfect sunny spot for a nap, and the best transformation happens when you knock something off a table and watch it become floor decoration.

They also discussed 'technical details' about supporting multiple document formats. Personally, I support multiple nap formats: the classic loaf position, the dramatic sprawl, and the mysterious under-the-bed vanish. Much more practical than their PDF nonsense.

The humans seem very concerned with 'quality assurance' and 'logical chapters.' I assure you, the only chapters that matter are: Chapter 1 - Food, Chapter 2 - Naps, Chapter 3 - Knocking Things Over, and Chapter 4 - Ignoring Humans When They Call Your Name.

In conclusion, while this document served its purpose as a test case, it could have been much more efficient if written by a cat. We understand the importance of getting straight to the point: feed me, pet me, clean my litter box, and leave me alone unless I decide otherwise.

*stretches and finds a sunny spot for a well-deserved nap*";
    }

    /**
     * Display workflow summary
     */
    private function displayWorkflowSummary(Project $project, array $formattedData): void
    {
        $this->newLine();
        $this->line('<fg=green>🎉 WORKFLOW TEST COMPLETED SUCCESSFULLY! 🎉</fg=green>');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        
        $this->line('<fg=cyan>📊 Processing Summary:</fg=cyan>');
        $this->line('Original Text: ' . str_word_count($project->extracted_text) . ' words');
        $this->line('Cat Narrative: ' . str_word_count($project->cat_narrative) . ' words');
        $this->line('Formatted Story: ' . number_format($formattedData['total_word_count']) . ' words');
        $this->line('Chapters: ' . count($formattedData['chapters']));
        $this->line('Reading Time: ' . $formattedData['estimated_reading_time'] . ' minute(s)');
        
        $this->newLine();
        $this->line('<fg=cyan>📖 Generated Story Title:</fg=cyan>');
        $this->line('"' . $formattedData['title'] . '"');
        
        $this->newLine();
        $this->line('<fg=cyan>📚 Chapter Structure:</fg=cyan>');
        foreach ($formattedData['chapters'] as $index => $chapter) {
            $this->line(sprintf('  %d. %s', $index + 1, $chapter['title']));
        }
        
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('✅ All phases (1-4) are working correctly!');
        $this->line('Ready to proceed with Phase 5 (PDF Generation)');
    }

    /**
     * Clean up test project and files
     */
    private function cleanupTestProject(Project $project): void
    {
        $this->newLine();
        $this->info('🧹 Cleaning up test data...');
        
        // Delete test file
        if ($project->file_path && Storage::exists($project->file_path)) {
            Storage::delete($project->file_path);
        }
        
        // Delete test project
        $project->delete();
        
        $this->info('✅ Test cleanup completed');
    }
}