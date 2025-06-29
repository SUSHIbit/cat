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
            'extracting_text', 'converting_to_cat', 'formatting', 'generating_pdf' => 'text-amber-600',
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
            'converting_to_cat' => 'Converting to Cat Speak',
            'formatting' => 'Formatting Story',
            'generating_pdf' => 'Generating PDF',
            'completed' => 'Completed',
            'failed' => 'Failed',
            default => 'Unknown',
        };
    }
}