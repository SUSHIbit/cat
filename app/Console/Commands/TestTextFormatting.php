<?php

namespace App\Console\Commands;

use App\Services\TextFormatter;
use Illuminate\Console\Command;

class TestTextFormatting extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:text-formatting';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the text formatting functionality with sample cat narrative';

    /**
     * Execute the console command.
     */
    public function handle(TextFormatter $formatter): int
    {
        $this->info('🐱 Testing Text Formatting Service...');
        $this->newLine();

        // Sample cat narrative for testing
        $sampleCatNarrative = "Let me tell you about this absolutely fascinating document I just witnessed being created. As a cat who has spent considerable time observing humans and their strange habits, I must say this was quite the spectacle.

First, the humans gathered around their glowing rectangles - you know, those things they stare at all day instead of paying proper attention to us cats. They were making all sorts of clicking noises with their little plastic mice. Quite amusing, really.

The whole process started when someone decided they needed to create something called a 'presentation.' I'm not entirely sure what that means, but it involved a lot of frantic typing and occasional sighs of frustration. As someone who has mastered the art of communication through strategic meowing, I found their approach rather inefficient.

Now, here's where it gets interesting. They spent hours arranging colorful rectangles on their screens, adding text that apparently needed to be 'just right.' Meanwhile, I demonstrated superior presentation skills by simply walking across their keyboard. My contribution was immediately appreciated with enthusiastic exclamations, though I suspect they may have been expressions of admiration rather than the mild complaints they appeared to be.

The humans also seemed obsessed with something called 'bullet points.' From my perspective as a cat who has successfully trained multiple humans, I can tell you that the most effective communication method is a single, well-timed meow. But they insisted on organizing everything into neat little lists.

Throughout this entire process, I provided valuable supervision by positioning myself strategically between the human and the screen. This technique, which I call 'helpful oversight,' ensures that no important work gets done without proper feline approval.

In conclusion, while humans have developed elaborate methods for sharing information, they could learn a lot from cats. We've mastered the art of getting our point across with minimal effort and maximum effect. Perhaps their next presentation should be about the superior communication skills of cats.";

        $this->line('Sample cat narrative:');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->line(substr($sampleCatNarrative, 0, 200) . '...');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->newLine();

        try {
            $this->info('🔄 Formatting cat narrative...');
            
            // Format the narrative
            $formattedData = $formatter->formatNarrative($sampleCatNarrative, 'Test Document');
            
            // Validate the formatted data
            $validationIssues = $formatter->validateFormattedNarrative($formattedData);
            
            if (!empty($validationIssues)) {
                $this->error('❌ Validation failed:');
                foreach ($validationIssues as $issue) {
                    $this->line('  • ' . $issue);
                }
                return 1;
            }
            
            $this->info('✅ Formatting successful!');
            $this->newLine();
            
            // Display results
            $this->line('<fg=cyan>📖 Formatted Story Details:</fg=cyan>');
            $this->line('Title: ' . $formattedData['title']);
            $this->line('Chapters: ' . count($formattedData['chapters']));
            $this->line('Total Words: ' . number_format($formattedData['total_word_count']));
            $this->line('Reading Time: ' . $formattedData['estimated_reading_time'] . ' minute(s)');
            $this->newLine();
            
            // Show chapter breakdown
            $this->line('<fg=cyan>📚 Chapter Breakdown:</fg=cyan>');
            foreach ($formattedData['chapters'] as $index => $chapter) {
                $this->line(sprintf(
                    "  %d. %s (%d words)",
                    $index + 1,
                    $chapter['title'],
                    $chapter['word_count']
                ));
            }
            $this->newLine();
            
            // Generate the complete formatted story
            $this->info('📝 Generating complete formatted story...');
            $formattedStory = $formatter->generateFormattedStory($formattedData);
            
            $this->info('✅ Complete story generated!');
            $this->line('Story length: ' . number_format(strlen($formattedStory)) . ' characters');
            $this->newLine();
            
            // Show first part of the formatted story
            $this->line('<fg=cyan>📖 Story Preview:</fg=cyan>');
            $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            $this->line(substr($formattedStory, 0, 500) . '...');
            $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            $this->newLine();
            
            $this->info('🎉 Text formatting test completed successfully!');
            $this->line('The TextFormatter service is working correctly and ready for use.');
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error('❌ Text formatting test failed: ' . $e->getMessage());
            $this->newLine();
            $this->line('Stack trace:');
            $this->line($e->getTraceAsString());
            return 1;
        }
    }
}