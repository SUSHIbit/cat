<?php

namespace App\Console\Commands;

use App\Services\PDFGenerator;
use App\Models\Project;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class TestPDFGeneration extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:pdf-generation {--project-id= : Specific project ID to test}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test PDF generation functionality';

    /**
     * Execute the console command.
     */
    public function handle(PDFGenerator $generator): int
    {
        $this->info('🐱 Testing PDF Generation...');
        $this->newLine();

        try {
            // Get project to test
            $project = $this->getTestProject();
            
            if (!$project) {
                $this->error('❌ No suitable project found for testing');
                $this->line('Please ensure you have a project with completed formatted narrative');
                return 1;
            }

            $this->info("📄 Testing with project: {$project->title} (ID: {$project->id})");
            $this->newLine();

            // Validate requirements
            $this->info('🔍 Validating PDF generation requirements...');
            $validationIssues = $generator->validateGenerationRequirements($project);
            
            if (!empty($validationIssues)) {
                $this->error('❌ Validation failed:');
                foreach ($validationIssues as $issue) {
                    $this->line('  • ' . $issue);
                }
                return 1;
            }
            
            $this->info('✅ Validation passed');
            $this->newLine();

            // Show generation estimates
            $this->line('<fg=cyan>📊 Generation Estimates:</fg=cyan>');
            $this->line('Estimated time: ' . $generator->estimateGenerationTime($project) . ' seconds');
            $this->line('Estimated size: ' . number_format($generator->estimatePDFSize($project)) . ' bytes');
            $this->newLine();

            // Generate PDF
            $this->info('🔄 Generating PDF...');
            $startTime = microtime(true);
            
            $pdfPath = $generator->generateCatNarrativePDF($project);
            
            $endTime = microtime(true);
            $actualTime = round($endTime - $startTime, 2);

            $this->info('✅ PDF generation completed!');
            $this->newLine();

            // Display results
            $this->line('<fg=green>🎉 PDF Generation Results:</fg=green>');
            $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            $this->line('PDF Path: ' . $pdfPath);
            $this->line('Generation Time: ' . $actualTime . ' seconds');
            
            if (Storage::exists($pdfPath)) {
                $actualSize = Storage::size($pdfPath);
                $this->line('Actual Size: ' . number_format($actualSize) . ' bytes (' . $this->formatBytes($actualSize) . ')');
                $this->line('File exists: ✅ Yes');
                
                // Test file accessibility
                $this->info('🔍 Testing file accessibility...');
                $content = Storage::get($pdfPath);
                if (substr($content, 0, 4) === '%PDF') {
                    $this->info('✅ PDF file format validation passed');
                } else {
                    $this->warn('⚠️  PDF file format might be invalid');
                }
                
            } else {
                $this->error('❌ Generated PDF file not found');
                return 1;
            }
            
            $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            $this->newLine();

            // Update project if requested
            if ($this->confirm('Update project with generated PDF path?', true)) {
                $project->update([
                    'pdf_path' => $pdfPath,
                    'status' => 'completed'
                ]);
                $this->info('✅ Project updated successfully');
            }

            // Cleanup option
            if ($this->confirm('Delete generated test PDF?', false)) {
                Storage::delete($pdfPath);
                $this->info('🧹 Test PDF deleted');
            } else {
                $this->line('📁 PDF saved at: storage/app/' . $pdfPath);
            }

            $this->newLine();
            $this->info('🎉 PDF generation test completed successfully!');
            
            return 0;

        } catch (\Exception $e) {
            $this->error('❌ PDF generation test failed: ' . $e->getMessage());
            $this->newLine();
            $this->line('Stack trace:');
            $this->line($e->getTraceAsString());
            return 1;
        }
    }

    /**
     * Get a project to test with
     */
    private function getTestProject(): ?Project
    {
        $projectId = $this->option('project-id');
        
        if ($projectId) {
            $project = Project::find($projectId);
            if (!$project) {
                $this->error("Project with ID {$projectId} not found");
                return null;
            }
            return $project;
        }

        // Find a project with formatted narrative
        $project = Project::whereNotNull('formatted_narrative')
            ->where('formatted_narrative', '!=', '')
            ->orderBy('updated_at', 'desc')
            ->first();

        if (!$project) {
            // Create a test project with sample data
            $this->warn('No projects with formatted narratives found. Creating test project...');
            return $this->createTestProject();
        }

        return $project;
    }

    /**
     * Create a test project with sample formatted narrative
     */
    private function createTestProject(): Project
    {
        $sampleNarrative = "# The Purrfect Tale of Office Life

_A feline perspective brought to you by a very sophisticated cat_

**Word Count:** 450 words  
**Estimated Reading Time:** 2 minute(s)

---

## Chapter 1: The Great Morning Observation

Let me tell you about this absolutely fascinating document I just witnessed being created, from my superior feline perspective.

As a cat who has spent considerable time observing humans and their strange document-creation rituals, I must say this morning was quite the spectacle. The humans seemed very focused on creating something they called a 'quarterly report' - though I'm not sure why they need to report anything when they could simply ask a cat for the correct approach.

The whole process started when someone decided they needed to document their 'achievements.' I found this amusing, as my daily achievements include perfect naps, strategic food procurement, and maintaining optimal sunbeam positioning - all far more impressive than whatever they were typing.

## Chapter 2: The Mysterious Spreadsheet Ritual

🐾 🐾 🐾

Now, here's where it gets interesting. They spent hours staring at something called a 'spreadsheet' filled with numbers. From my elevated position on the desk (clearly the best vantage point for supervision), I could see these numbers meant something important to them.

As someone who has successfully trained multiple humans through strategic meowing, I can tell you that the most effective metrics are simple: food bowl fullness, scratch-behind-ears frequency, and optimal napping spot availability. But they insisted on tracking things like 'revenue' and 'growth targets.'

## Chapter 3: The Art of Perfect Presentation

Throughout this entire process, I provided valuable supervision by positioning myself strategically between the human and the screen. This technique, which I call 'helpful oversight,' ensures that no important work gets done without proper feline approval.

They seemed particularly concerned with something called 'formatting' and 'visual appeal.' Having mastered the art of presentation myself (have you seen how perfectly I arrange myself in a sunbeam?), I appreciated their attention to aesthetics.

---

_The End_

🐱 **Purr-fectly narrated by your friendly neighborhood cat** 🐱";

        return Project::create([
            'title' => 'Test Project for PDF Generation',
            'original_filename' => 'test_document.txt',
            'file_path' => 'test/sample.txt',
            'file_type' => 'txt',
            'file_size' => strlen($sampleNarrative),
            'status' => 'generating_pdf',
            'extracted_text' => 'Sample extracted text for testing PDF generation functionality.',
            'cat_narrative' => 'Sample cat narrative for testing PDF generation.',
            'formatted_narrative' => $sampleNarrative,
        ]);
    }

    /**
     * Format bytes into human readable format
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
}