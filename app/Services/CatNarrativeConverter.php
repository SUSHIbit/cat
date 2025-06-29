<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Log;
use OpenAI\Client;

class CatNarrativeConverter
{
    private Client $openai;
    private int $maxTokens = 3000;
    private float $temperature = 0.8;
    private string $model = 'gpt-3.5-turbo';

    public function __construct()
    {
        $this->openai = app('openai');
    }

    /**
     * Convert text to cat narrative
     */
    public function convertToCatNarrative(string $text): string
    {
        try {
            // Validate input text
            if (empty(trim($text))) {
                throw new Exception("Input text is empty");
            }

            // Split text into chunks if it's too long
            $chunks = $this->splitTextIntoChunks($text, 2000);
            $convertedChunks = [];

            foreach ($chunks as $index => $chunk) {
                Log::info("Converting chunk " . ($index + 1) . " of " . count($chunks));
                
                $convertedChunk = $this->convertChunkToCatSpeak($chunk);
                $convertedChunks[] = $convertedChunk;
                
                // Add small delay to respect rate limits
                if (count($chunks) > 1 && $index < count($chunks) - 1) {
                    sleep(1);
                }
            }

            // Combine all converted chunks
            $fullNarrative = implode("\n\n", $convertedChunks);
            
            // Post-process the narrative for consistency
            return $this->postProcessNarrative($fullNarrative);
            
        } catch (Exception $e) {
            Log::error("Cat narrative conversion failed: " . $e->getMessage());
            throw new Exception("Failed to convert text to cat narrative: " . $e->getMessage());
        }
    }

    /**
     * Convert a single chunk of text to cat speak
     */
    private function convertChunkToCatSpeak(string $chunk): string
    {
        $prompt = $this->buildCatNarrativePrompt($chunk);
        
        try {
            $response = $this->openai->chat()->create([
                'model' => $this->model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => $this->getSystemPrompt()
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'max_tokens' => $this->maxTokens,
                'temperature' => $this->temperature,
                'top_p' => 0.9,
                'frequency_penalty' => 0.1,
                'presence_penalty' => 0.1,
            ]);

            $content = $response->choices[0]->message->content ?? '';
            
            if (empty(trim($content))) {
                throw new Exception("OpenAI returned empty response");
            }

            return trim($content);
            
        } catch (Exception $e) {
            Log::error("OpenAI API error: " . $e->getMessage());
            throw new Exception("OpenAI conversion failed: " . $e->getMessage());
        }
    }

    /**
     * Get the system prompt for cat narrative conversion
     */
    private function getSystemPrompt(): string
    {
        return "You are a creative writer who specializes in transforming any text into entertaining narratives told from a cat's perspective. Your job is to rewrite content as if an intelligent, witty, and slightly sarcastic cat is telling the story. 

Key characteristics:
- Use cat-related expressions and metaphors naturally
- Maintain the core information while making it entertaining
- Add feline personality: curious, independent, occasionally dramatic
- Include subtle cat behaviors and observations
- Keep the tone engaging and humorous without being overly silly
- Preserve important facts and structure while adding cat flair
- Use proper narrative flow with good pacing

Always write in first person from the cat's perspective, as if the cat experienced or observed these events.";
    }

    /**
     * Build the specific prompt for converting text
     */
    private function buildCatNarrativePrompt(string $text): string
    {
        return "Transform the following text into an entertaining story narrated by a clever cat. Keep all the important information but tell it as if you're a cat who witnessed or experienced these events. Make it engaging and fun while preserving the key details:

--- TEXT TO TRANSFORM ---
{$text}
--- END TEXT ---

Write this as a cohesive cat narrative that flows naturally and entertains the reader while maintaining the essential information.";
    }

    /**
     * Split long text into manageable chunks
     */
    private function splitTextIntoChunks(string $text, int $maxChunkSize = 2000): array
    {
        if (strlen($text) <= $maxChunkSize) {
            return [$text];
        }

        $chunks = [];
        $sentences = preg_split('/(?<=[.!?])\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        
        $currentChunk = '';
        
        foreach ($sentences as $sentence) {
            // If adding this sentence would exceed the limit, save current chunk
            if (!empty($currentChunk) && strlen($currentChunk . ' ' . $sentence) > $maxChunkSize) {
                $chunks[] = trim($currentChunk);
                $currentChunk = $sentence;
            } else {
                $currentChunk .= (empty($currentChunk) ? '' : ' ') . $sentence;
            }
        }
        
        // Don't forget the last chunk
        if (!empty($currentChunk)) {
            $chunks[] = trim($currentChunk);
        }
        
        return array_filter($chunks); // Remove any empty chunks
    }

    /**
     * Post-process the narrative for consistency and flow
     */
    private function postProcessNarrative(string $narrative): string
    {
        // Clean up any formatting issues
        $narrative = preg_replace('/\n{3,}/', "\n\n", $narrative);
        $narrative = trim($narrative);
        
        // Ensure we have a proper ending if the narrative seems cut off
        if (!preg_match('/[.!?]$/', $narrative)) {
            $narrative .= '.';
        }
        
        return $narrative;
    }

    /**
     * Estimate processing time based on text length
     */
    public function estimateProcessingTime(string $text): int
    {
        $chunks = $this->splitTextIntoChunks($text, 2000);
        $baseTimePerChunk = 10; // seconds
        $delayBetweenChunks = 1; // second
        
        return (count($chunks) * $baseTimePerChunk) + ((count($chunks) - 1) * $delayBetweenChunks);
    }

    /**
     * Validate OpenAI configuration
     */
    public function validateConfiguration(): bool
    {
        try {
            $apiKey = config('services.openai.api_key');
            
            if (empty($apiKey)) {
                Log::error("OpenAI API key not configured");
                return false;
            }
            
            // Test with a simple request
            $response = $this->openai->chat()->create([
                'model' => $this->model,
                'messages' => [
                    ['role' => 'user', 'content' => 'Say "meow" if you can hear me.']
                ],
                'max_tokens' => 10,
            ]);
            
            return !empty($response->choices[0]->message->content);
            
        } catch (Exception $e) {
            Log::error("OpenAI configuration validation failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Set custom model for conversion
     */
    public function setModel(string $model): self
    {
        $this->model = $model;
        return $this;
    }

    /**
     * Set custom temperature for creativity
     */
    public function setTemperature(float $temperature): self
    {
        $this->temperature = max(0.0, min(2.0, $temperature));
        return $this;
    }
}