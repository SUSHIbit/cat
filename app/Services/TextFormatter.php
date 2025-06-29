<?php

namespace App\Services;

use Illuminate\Support\Str;

class TextFormatter
{
    private array $catTitleTemplates = [
        "The Purrfect Tale of {topic}",
        "A Cat's Eye View of {topic}",
        "Whiskers and Wisdom: {topic}",
        "The Feline Chronicles: {topic}",
        "Meow-sings on {topic}",
        "Paws and Reflect: {topic}",
        "The Cat's Meow About {topic}",
        "Fur Real Stories: {topic}",
        "Nine Lives and {topic}",
        "The Purr-fessional Guide to {topic}"
    ];

    /**
     * Format cat narrative into structured story format
     */
    public function formatNarrative(string $catNarrative, string $originalTitle = null): array
    {
        // Generate a cat-themed title
        $formattedTitle = $this->generateCatTitle($originalTitle ?? 'This Adventure');
        
        // Split narrative into chapters/sections
        $chapters = $this->splitIntoChapters($catNarrative);
        
        // Format each chapter
        $formattedChapters = [];
        foreach ($chapters as $index => $chapter) {
            $formattedChapters[] = [
                'title' => $this->generateChapterTitle($index + 1, $chapter),
                'content' => $this->formatChapterContent($chapter),
                'word_count' => str_word_count($chapter)
            ];
        }
        
        return [
            'title' => $formattedTitle,
            'chapters' => $formattedChapters,
            'total_word_count' => array_sum(array_column($formattedChapters, 'word_count')),
            'estimated_reading_time' => $this->calculateReadingTime(array_sum(array_column($formattedChapters, 'word_count'))),
            'formatted_at' => now()
        ];
    }

    /**
     * Generate a cat-themed title
     */
    private function generateCatTitle(string $originalTitle): string
    {
        // Extract key topic from original title
        $topic = $this->extractMainTopic($originalTitle);
        
        // Select random template
        $template = $this->catTitleTemplates[array_rand($this->catTitleTemplates)];
        
        return str_replace('{topic}', $topic, $template);
    }

    /**
     * Extract main topic from title
     */
    private function extractMainTopic(string $title): string
    {
        // Remove common words and clean up
        $stopWords = ['the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by', 'about'];
        
        $words = explode(' ', strtolower($title));
        $meaningfulWords = array_filter($words, function($word) use ($stopWords) {
            return !in_array(trim($word), $stopWords) && strlen(trim($word)) > 2;
        });
        
        if (empty($meaningfulWords)) {
            return 'My Adventure';
        }
        
        // Take first few meaningful words
        $topicWords = array_slice($meaningfulWords, 0, 3);
        return ucwords(implode(' ', $topicWords));
    }

    /**
     * Split narrative into logical chapters
     */
    private function splitIntoChapters(string $narrative): array
    {
        // First, try to split by obvious chapter markers
        $chapterMarkers = [
            '/Chapter \d+/i',
            '/Part \d+/i',
            '/Section \d+/i'
        ];
        
        foreach ($chapterMarkers as $marker) {
            if (preg_match($marker, $narrative)) {
                return preg_split($marker, $narrative, -1, PREG_SPLIT_NO_EMPTY);
            }
        }
        
        // If no obvious markers, split by content length and natural breaks
        $paragraphs = $this->splitIntoParagraphs($narrative);
        $chapters = [];
        $currentChapter = '';
        $targetWordsPerChapter = 200; // Aim for roughly 200 words per chapter
        $currentWordCount = 0;
        
        foreach ($paragraphs as $paragraph) {
            $paragraphWordCount = str_word_count($paragraph);
            
            // If adding this paragraph would make chapter too long, start new chapter
            if ($currentWordCount > 0 && ($currentWordCount + $paragraphWordCount) > $targetWordsPerChapter) {
                $chapters[] = trim($currentChapter);
                $currentChapter = $paragraph;
                $currentWordCount = $paragraphWordCount;
            } else {
                $currentChapter .= ($currentChapter ? "\n\n" : '') . $paragraph;
                $currentWordCount += $paragraphWordCount;
            }
        }
        
        // Don't forget the last chapter
        if (!empty(trim($currentChapter))) {
            $chapters[] = trim($currentChapter);
        }
        
        // Ensure we have at least one chapter
        if (empty($chapters)) {
            $chapters = [$narrative];
        }
        
        return $chapters;
    }

    /**
     * Split text into paragraphs
     */
    private function splitIntoParagraphs(string $text): array
    {
        // Split by double line breaks or clear paragraph indicators
        $paragraphs = preg_split('/\n\s*\n/', $text);
        
        // Clean up and filter empty paragraphs
        return array_filter(array_map('trim', $paragraphs), function($p) {
            return !empty($p) && strlen($p) > 10; // Skip very short paragraphs
        });
    }

    /**
     * Generate chapter title based on content
     */
    private function generateChapterTitle(int $chapterNumber, string $content): string
    {
        $catChapterTitles = [
            "Whisker Twitches and Discoveries",
            "The Great Nap Interruption", 
            "Adventures in the Sunbeam",
            "When the Red Dot Appeared",
            "The Mystery of the Empty Food Bowl",
            "Paws for Thought",
            "The Cardboard Castle Chronicles",
            "Midnight Zoomies and Revelations",
            "The Curious Case of the Catnip",
            "Purrs and Ponderings",
            "The Great Window Watch",
            "Tales from the Cat Tree",
            "The Afternoon Contemplation",
            "Whiskers in the Wind",
            "The Epic Yarn Ball Saga"
        ];
        
        // Try to generate contextual title based on content
        $contextualTitle = $this->generateContextualTitle($content);
        if ($contextualTitle) {
            return "Chapter {$chapterNumber}: {$contextualTitle}";
        }
        
        // Fall back to random cat-themed title
        $randomTitle = $catChapterTitles[array_rand($catChapterTitles)];
        return "Chapter {$chapterNumber}: {$randomTitle}";
    }

    /**
     * Generate contextual title based on chapter content
     */
    private function generateContextualTitle(string $content): ?string
    {
        // Look for key actions or themes in the content
        $keyPhrases = [
            '/sleep|nap|snooze/i' => 'The Art of Perfect Napping',
            '/food|eat|hungry|fish/i' => 'Culinary Adventures',
            '/play|toy|mouse|ball/i' => 'Playtime Chronicles',
            '/window|outside|bird/i' => 'Window Watching Wisdom',
            '/human|owner|pet/i' => 'Human Relations',
            '/curious|explore|discover/i' => 'Curious Investigations',
            '/comfortable|cozy|warm/i' => 'Comfort Seeking',
            '/hunt|catch|pounce/i' => 'The Hunter\'s Tale'
        ];
        
        foreach ($keyPhrases as $pattern => $title) {
            if (preg_match($pattern, $content)) {
                return $title;
            }
        }
        
        return null;
    }

    /**
     * Format chapter content with proper structure
     */
    private function formatChapterContent(string $content): string
    {
        // Clean up the content
        $content = trim($content);
        
        // Ensure proper paragraph breaks
        $content = preg_replace('/\n{3,}/', "\n\n", $content);
        
        // Add proper indentation for dialogue if present
        $content = preg_replace('/^"([^"]*)"$/m', '    "$1"', $content);
        
        // Ensure sentences end properly
        $content = preg_replace('/([.!?])\s*([A-Z])/', '$1 $2', $content);
        
        return $content;
    }

    /**
     * Calculate estimated reading time in minutes
     */
    private function calculateReadingTime(int $wordCount): int
    {
        // Average reading speed is about 200-250 words per minute
        // We'll use 225 as a middle ground
        return max(1, (int) ceil($wordCount / 225));
    }

    /**
     * Generate a complete formatted narrative as a single string
     */
    public function generateFormattedStory(array $formattedData): string
    {
        $story = "# " . $formattedData['title'] . "\n\n";
        
        // Add story metadata
        $story .= "_A feline perspective brought to you by a very sophisticated cat_\n\n";
        $story .= "**Word Count:** " . number_format($formattedData['total_word_count']) . " words  \n";
        $story .= "**Estimated Reading Time:** " . $formattedData['estimated_reading_time'] . " minute(s)\n\n";
        $story .= "---\n\n";
        
        // Add each chapter
        foreach ($formattedData['chapters'] as $chapter) {
            $story .= "## " . $chapter['title'] . "\n\n";
            $story .= $chapter['content'] . "\n\n";
            
            // Add paw print separator between chapters (except for last one)
            if ($chapter !== end($formattedData['chapters'])) {
                $story .= "🐾 🐾 🐾\n\n";
            }
        }
        
        // Add closing
        $story .= "---\n\n";
        $story .= "_The End_\n\n";
        $story .= "🐱 **Purr-fectly narrated by your friendly neighborhood cat** 🐱";
        
        return $story;
    }

    /**
     * Validate formatted narrative structure
     */
    public function validateFormattedNarrative(array $formattedData): array
    {
        $issues = [];
        
        if (empty($formattedData['title'])) {
            $issues[] = 'Missing story title';
        }
        
        if (empty($formattedData['chapters']) || !is_array($formattedData['chapters'])) {
            $issues[] = 'No chapters found';
        } else {
            foreach ($formattedData['chapters'] as $index => $chapter) {
                if (empty($chapter['title'])) {
                    $issues[] = "Chapter " . ($index + 1) . " missing title";
                }
                if (empty($chapter['content'])) {
                    $issues[] = "Chapter " . ($index + 1) . " has no content";
                }
                if (($chapter['word_count'] ?? 0) < 10) {
                    $issues[] = "Chapter " . ($index + 1) . " is too short";
                }
            }
        }
        
        return $issues;
    }
}