<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'original_filename',
        'file_path',
        'file_type',
        'file_size',
        'status',
        'extracted_text',
        'cat_narrative',
        'formatted_narrative',
        'pdf_path',
        'error_message',
    ];

    protected $casts = [
        'file_size' => 'integer',
    ];

    public function getFileSizeHumanAttribute(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'uploaded' => 'text-slate-600',
            'extracting_text', 'text_extracted', 'converting_to_cat', 'formatting', 'generating_pdf' => 'text-amber-600',
            'completed' => 'text-emerald-600',
            'failed' => 'text-red-600',
            default => 'text-slate-600',
        };
    }

    public function getStatusDisplayAttribute(): string
    {
        return match($this->status) {
            'uploaded' => 'Uploaded',
            'extracting_text' => 'Extracting Text',
            'text_extracted' => 'Text Extracted',
            'converting_to_cat' => 'Converting to Cat Speak',
            'formatting' => 'Formatting Story',
            'generating_pdf' => 'Generating PDF',
            'completed' => 'Completed',
            'failed' => 'Failed',
            default => 'Unknown',
        };
    }

    /**
     * Get word count for extracted text
     */
    public function getExtractedTextWordCountAttribute(): int
    {
        return $this->extracted_text ? str_word_count($this->extracted_text) : 0;
    }

    /**
     * Get word count for cat narrative
     */
    public function getCatNarrativeWordCountAttribute(): int
    {
        return $this->cat_narrative ? str_word_count($this->cat_narrative) : 0;
    }

    /**
     * Get word count for formatted narrative
     */
    public function getFormattedNarrativeWordCountAttribute(): int
    {
        return $this->formatted_narrative ? str_word_count($this->formatted_narrative) : 0;
    }

    /**
     * Check if the project has a formatted narrative ready
     */
    public function hasFormattedNarrative(): bool
    {
        return !empty($this->formatted_narrative) && 
               in_array($this->status, ['generating_pdf', 'completed']);
    }

    /**
     * Get a preview of the formatted narrative (first 500 characters)
     */
    public function getFormattedNarrativePreviewAttribute(): string
    {
        if (!$this->formatted_narrative) {
            return '';
        }
        
        // Remove markdown formatting for preview
        $preview = strip_tags(str_replace(['#', '*', '_'], '', $this->formatted_narrative));
        
        return strlen($preview) > 500 
            ? substr($preview, 0, 500) . '...'
            : $preview;
    }

    /**
     * Extract the title from formatted narrative
     */
    public function getFormattedTitleAttribute(): ?string
    {
        if (!$this->formatted_narrative) {
            return null;
        }
        
        // Look for markdown h1 title
        if (preg_match('/^# (.+)$/m', $this->formatted_narrative, $matches)) {
            return trim($matches[1]);
        }
        
        return null;
    }

    /**
     * Check if project is currently being processed
     */
    public function isProcessing(): bool
    {
        return in_array($this->status, [
            'extracting_text', 
            'converting_to_cat', 
            'formatting', 
            'generating_pdf'
        ]);
    }

    /**
     * Get processing progress percentage
     */
    public function getProcessingProgressAttribute(): int
    {
        return match($this->status) {
            'uploaded' => 0,
            'extracting_text' => 20,
            'text_extracted' => 30,
            'converting_to_cat' => 60,
            'formatting' => 80,
            'generating_pdf' => 90,
            'completed' => 100,
            'failed' => 0,
            default => 0,
        };
    }

    /**
     * Get estimated completion time based on current status
     */
    public function getEstimatedCompletionAttribute(): ?string
    {
        if (!$this->isProcessing()) {
            return null;
        }
        
        $estimatedMinutes = match($this->status) {
            'extracting_text' => 2,
            'converting_to_cat' => 5,
            'formatting' => 1,
            'generating_pdf' => 2,
            default => 1,
        };
        
        return now()->addMinutes($estimatedMinutes)->format('g:i A');
    }
}