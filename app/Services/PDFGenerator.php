<?php

namespace App\Services;

use App\Models\Project;
use TCPDF;
use Exception;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class PDFGenerator
{
    private TCPDF $pdf;
    private array $config;
    
    public function __construct()
    {
        $this->config = [
            'page_format' => 'A4',
            'page_orientation' => 'P',
            'page_unit' => 'mm',
            'unicode' => true,
            'encoding' => 'UTF-8',
            'disable_disk_cache' => true,
        ];
    }

    /**
     * Generate PDF from formatted cat narrative
     */
    public function generateCatNarrativePDF(Project $project): string
    {
        try {
            if (empty($project->formatted_narrative)) {
                throw new Exception("No formatted narrative found for PDF generation");
            }

            // Initialize PDF
            $this->initializePDF($project);
            
            // Add cover page
            $this->addCoverPage($project);
            
            // Add story content
            $this->addStoryContent($project);
            
            // Add footer page
            $this->addFooterPage($project);
            
            // Generate and save PDF
            return $this->savePDF($project);
            
        } catch (Exception $e) {
            Log::error("PDF generation failed for project {$project->id}: " . $e->getMessage());
            throw new Exception("Failed to generate PDF: " . $e->getMessage());
        }
    }

    /**
     * Initialize PDF with cat-themed styling
     */
    private function initializePDF(Project $project): void
    {
        $this->pdf = new TCPDF(
            $this->config['page_orientation'],
            $this->config['page_unit'],
            $this->config['page_format'],
            $this->config['unicode'],
            $this->config['encoding'],
            $this->config['disable_disk_cache']
        );

        // Set document information
        $title = $this->extractTitleFromNarrative($project->formatted_narrative) ?: $project->title;
        $this->pdf->SetCreator('Cat Narrative Generator');
        $this->pdf->SetAuthor('A Very Sophisticated Cat');
        $this->pdf->SetTitle($title);
        $this->pdf->SetSubject('Cat Narrative Story');
        $this->pdf->SetKeywords('cat, narrative, story, entertainment');

        // Set default header and footer fonts
        $this->pdf->setHeaderFont(['dejavusans', '', 12]);
        $this->pdf->setFooterFont(['dejavusans', '', 10]);

        // Set margins
        $this->pdf->SetMargins(20, 25, 20);
        $this->pdf->SetHeaderMargin(5);
        $this->pdf->SetFooterMargin(10);

        // Set auto page breaks
        $this->pdf->SetAutoPageBreak(true, 25);

        // Remove default header/footer
        $this->pdf->setPrintHeader(false);
        $this->pdf->setPrintFooter(false);

        // Set image scale factor
        $this->pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

        // Set font
        $this->pdf->SetFont('dejavusans', '', 11);
    }

    /**
     * Add decorative cover page
     */
    private function addCoverPage(Project $project): void
    {
        $this->pdf->AddPage();
        
        // Title area with cat theme
        $this->pdf->SetFont('dejavusans', 'B', 24);
        $this->pdf->SetTextColor(51, 65, 85); // Slate-700
        
        $title = $this->extractTitleFromNarrative($project->formatted_narrative) ?: $project->title;
        
        // Add some vertical spacing
        $this->pdf->Ln(40);
        
        // Add decorative elements
        $this->pdf->SetFont('dejavusans', '', 48);
        $this->pdf->Cell(0, 20, '🐱', 0, 1, 'C');
        $this->pdf->Ln(10);
        
        // Main title
        $this->pdf->SetFont('dejavusans', 'B', 20);
        $this->pdf->MultiCell(0, 12, $title, 0, 'C', false, 1);
        $this->pdf->Ln(15);
        
        // Subtitle
        $this->pdf->SetFont('dejavusans', 'I', 14);
        $this->pdf->SetTextColor(100, 116, 139); // Slate-500
        $this->pdf->MultiCell(0, 8, 'A Feline Perspective', 0, 'C', false, 1);
        $this->pdf->Ln(5);
        $this->pdf->MultiCell(0, 8, 'Brought to you by a very sophisticated cat', 0, 'C', false, 1);
        
        // Document info
        $this->pdf->Ln(30);
        $this->pdf->SetFont('dejavusans', '', 10);
        $this->pdf->SetTextColor(148, 163, 184); // Slate-400
        
        $wordCount = str_word_count($project->formatted_narrative);
        $readingTime = max(1, ceil($wordCount / 225));
        
        $info = "Word Count: " . number_format($wordCount) . " words\n";
        $info .= "Estimated Reading Time: {$readingTime} minute(s)\n";
        $info .= "Generated: " . now()->format('F j, Y');
        
        $this->pdf->MultiCell(0, 5, $info, 0, 'C', false, 1);
        
        // Decorative footer
        $this->pdf->Ln(20);
        $this->pdf->SetFont('dejavusans', '', 12);
        $this->pdf->Cell(0, 8, '🐾 🐾 🐾', 0, 1, 'C');
    }

    /**
     * Add main story content with formatting
     */
    private function addStoryContent(Project $project): void
    {
        $this->pdf->AddPage();
        
        // Reset text color for content
        $this->pdf->SetTextColor(30, 41, 59); // Slate-800
        
        // Process the narrative content
        $content = $this->processNarrativeContent($project->formatted_narrative);
        
        // Split content into sections
        $sections = $this->splitContentIntoSections($content);
        
        foreach ($sections as $section) {
            $this->addSection($section);
        }
    }

    /**
     * Process narrative content for PDF formatting
     */
    private function processNarrativeContent(string $narrative): string
    {
        // Remove title from content (already on cover)
        $content = preg_replace('/^# .+$/m', '', $narrative);
        
        // Clean up excessive whitespace
        $content = preg_replace('/\n{3,}/', "\n\n", $content);
        
        return trim($content);
    }

    /**
     * Split content into manageable sections
     */
    private function splitContentIntoSections(string $content): array
    {
        $sections = [];
        
        // Split by chapter headers (## Chapter Title)
        $parts = preg_split('/^## (.+)$/m', $content, -1, PREG_SPLIT_DELIM_CAPTURE);
        
        // First part is content before any chapters
        if (!empty(trim($parts[0]))) {
            $sections[] = [
                'type' => 'intro',
                'title' => null,
                'content' => trim($parts[0])
            ];
        }
        
        // Process chapter pairs (title, content)
        for ($i = 1; $i < count($parts); $i += 2) {
            if (isset($parts[$i + 1])) {
                $sections[] = [
                    'type' => 'chapter',
                    'title' => trim($parts[$i]),
                    'content' => trim($parts[$i + 1])
                ];
            }
        }
        
        return $sections;
    }

    /**
     * Add a section to the PDF
     */
    private function addSection(array $section): void
    {
        // Add chapter title if present
        if ($section['title']) {
            // Check if we need a new page for the chapter
            if ($this->pdf->GetY() > 200) {
                $this->pdf->AddPage();
            }
            
            $this->pdf->Ln(8);
            $this->pdf->SetFont('dejavusans', 'B', 16);
            $this->pdf->SetTextColor(51, 65, 85); // Slate-700
            $this->pdf->MultiCell(0, 8, $section['title'], 0, 'L', false, 1);
            $this->pdf->Ln(4);
            
            // Add decorative element
            $this->pdf->SetFont('dejavusans', '', 10);
            $this->pdf->Cell(0, 4, '🐾 🐾 🐾', 0, 1, 'C');
            $this->pdf->Ln(4);
        }
        
        // Add content
        $this->pdf->SetFont('dejavusans', '', 11);
        $this->pdf->SetTextColor(30, 41, 59); // Slate-800
        
        // Process paragraphs
        $paragraphs = explode("\n\n", $section['content']);
        
        foreach ($paragraphs as $paragraph) {
            $paragraph = trim($paragraph);
            if (empty($paragraph)) continue;
            
            // Handle special formatting
            if (strpos($paragraph, '**') !== false) {
                // Bold text
                $paragraph = str_replace(['**', '**'], ['<b>', '</b>'], $paragraph);
                $this->pdf->writeHTML($paragraph, true, false, true, false, '');
            } elseif (strpos($paragraph, '_') !== false && substr_count($paragraph, '_') >= 2) {
                // Italic text
                $paragraph = preg_replace('/_([^_]+)_/', '<i>$1</i>', $paragraph);
                $this->pdf->writeHTML($paragraph, true, false, true, false, '');
            } else {
                // Regular paragraph
                $this->pdf->MultiCell(0, 6, $paragraph, 0, 'L', false, 1);
            }
            
            $this->pdf->Ln(4);
        }
        
        $this->pdf->Ln(4);
    }

    /**
     * Add footer page with cat-themed ending
     */
    private function addFooterPage(Project $project): void
    {
        $this->pdf->AddPage();
        
        // Center vertically
        $this->pdf->Ln(60);
        
        // "The End" section
        $this->pdf->SetFont('dejavusans', 'B', 18);
        $this->pdf->SetTextColor(51, 65, 85); // Slate-700
        $this->pdf->Cell(0, 12, 'The End', 0, 1, 'C');
        $this->pdf->Ln(10);
        
        // Cat signature
        $this->pdf->SetFont('dejavusans', '', 14);
        $this->pdf->SetTextColor(100, 116, 139); // Slate-500
        $this->pdf->Cell(0, 8, '🐱 Purr-fectly narrated by your friendly neighborhood cat 🐱', 0, 1, 'C');
        $this->pdf->Ln(20);
        
        // Generation info
        $this->pdf->SetFont('dejavusans', '', 9);
        $this->pdf->SetTextColor(148, 163, 184); // Slate-400
        
        $info = "Generated by Cat Narrative Generator\n";
        $info .= "Original document: {$project->original_filename}\n";
        $info .= "Created: " . now()->format('F j, Y \a\t g:i A');
        
        $this->pdf->MultiCell(0, 4, $info, 0, 'C', false, 1);
        
        // Final decorative elements
        $this->pdf->Ln(15);
        $this->pdf->SetFont('dejavusans', '', 16);
        $this->pdf->Cell(0, 8, '🐾  🐱  🐾', 0, 1, 'C');
    }

    /**
     * Save PDF and return file path
     */
    private function savePDF(Project $project): string
    {
        $filename = 'cat_narrative_' . $project->id . '_' . time() . '.pdf';
        $filePath = 'pdfs/' . $filename;
        
        // Generate PDF content
        $pdfContent = $this->pdf->Output('', 'S');
        
        // Save to storage
        Storage::put($filePath, $pdfContent);
        
        Log::info("PDF generated successfully for project {$project->id}", [
            'file_path' => $filePath,
            'file_size' => strlen($pdfContent)
        ]);
        
        return $filePath;
    }

    /**
     * Extract title from formatted narrative
     */
    private function extractTitleFromNarrative(string $narrative): ?string
    {
        // Look for markdown h1 title
        if (preg_match('/^# (.+)$/m', $narrative, $matches)) {
            return trim($matches[1]);
        }
        
        return null;
    }

    /**
     * Validate PDF generation requirements
     */
    public function validateGenerationRequirements(Project $project): array
    {
        $issues = [];
        
        if (empty($project->formatted_narrative)) {
            $issues[] = 'No formatted narrative available for PDF generation';
        }
        
        if (strlen($project->formatted_narrative) < 100) {
            $issues[] = 'Formatted narrative is too short for PDF generation';
        }
        
        if ($project->status !== 'generating_pdf' && $project->status !== 'completed') {
            $issues[] = 'Project is not ready for PDF generation';
        }
        
        return $issues;
    }

    /**
     * Estimate PDF generation time
     */
    public function estimateGenerationTime(Project $project): int
    {
        $wordCount = str_word_count($project->formatted_narrative ?? '');
        $baseTime = 5; // 5 seconds minimum
        $wordFactor = max(1, $wordCount / 1000); // 1 second per 1000 words
        
        return $baseTime + (int)$wordFactor;
    }

    /**
     * Get PDF file size estimate
     */
    public function estimatePDFSize(Project $project): int
    {
        $contentLength = strlen($project->formatted_narrative ?? '');
        $baseSize = 50000; // 50KB base size
        $contentFactor = $contentLength * 0.5; // Rough estimation
        
        return (int)($baseSize + $contentFactor);
    }
}