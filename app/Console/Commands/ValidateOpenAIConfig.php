<?php

namespace App\Console\Commands;

use App\Services\CatNarrativeConverter;
use Illuminate\Console\Command;

class ValidateOpenAIConfig extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'openai:validate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Validate OpenAI configuration and test connectivity';

    /**
     * Execute the console command.
     */
    public function handle(CatNarrativeConverter $converter): int
    {
        $this->info('🐱 Validating OpenAI Configuration...');
        $this->newLine();

        // Check API key
        $apiKey = config('services.openai.api_key');
        if (empty($apiKey)) {
            $this->error('❌ OPENAI_API_KEY environment variable is not set');
            $this->line('Please add your OpenAI API key to your .env file:');
            $this->line('OPENAI_API_KEY=your_api_key_here');
            return 1;
        }

        $this->info('✅ OpenAI API key is configured');

        // Check organization (optional)
        $organization = config('services.openai.organization');
        if ($organization) {
            $this->info('✅ OpenAI organization ID is configured');
        } else {
            $this->warn('⚠️  OpenAI organization ID is not set (this is optional)');
        }

        // Test API connectivity
        $this->info('🔍 Testing OpenAI API connectivity...');
        
        try {
            if ($converter->validateConfiguration()) {
                $this->info('✅ OpenAI API connection successful!');
                
                // Test cat narrative conversion with a small sample
                $this->info('🧪 Testing cat narrative conversion...');
                $testText = "This is a test document. It contains some sample text for testing the cat narrative conversion functionality.";
                
                $result = $converter->convertToCatNarrative($testText);
                
                if (!empty($result)) {
                    $this->info('✅ Cat narrative conversion test successful!');
                    $this->newLine();
                    $this->line('Sample output:');
                    $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
                    $this->line($result);
                    $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
                    $this->newLine();
                    $this->info('🎉 All OpenAI configuration tests passed!');
                    return 0;
                } else {
                    $this->error('❌ Cat narrative conversion test failed - empty result');
                    return 1;
                }
            } else {
                $this->error('❌ OpenAI API connection failed');
                $this->line('Please check your API key and internet connection');
                return 1;
            }
        } catch (\Exception $e) {
            $this->error('❌ OpenAI API test failed: ' . $e->getMessage());
            $this->newLine();
            $this->line('Common issues:');
            $this->line('• Invalid API key');
            $this->line('• No internet connection');
            $this->line('• OpenAI API rate limits exceeded');
            $this->line('• Insufficient OpenAI credits');
            return 1;
        }
    }
}