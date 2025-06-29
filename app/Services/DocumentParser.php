<?php

namespace App\Services;

use Exception;
use PhpOffice\PhpWord\IOFactory as WordIOFactory;
use PhpOffice\PhpPresentation\IOFactory as PresentationIOFactory;
use Smalot\PdfParser\Parser as PdfParser;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class DocumentParser
{
    /**
     * Extract text from various document types
     */
    public function extractText(string $filePath, string $fileType): string
    {
        try {
            $fullPath = Storage::path($filePath);
            
            if (!file_exists($fullPath)) {
                throw new Exception("File not found: {$fullPath}");
            }

            return match (strtolower($fileType)) {
                'docx' => $this->extractFromWord($fullPath),
                'pdf' => $this->extractFromPdf($fullPath),
                'pptx' => $this->extractFromPowerPoint($fullPath),
                default => throw new Exception("Unsupported file type: {$fileType}")
            };
        } catch (Exception $e) {
            Log::error("Error extracting text from {$fileType} file: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Extract text from Word documents (.docx)
     */
    private function extractFromWord(string $filePath): string
    {
        try {
            $phpWord = WordIOFactory::load($filePath);
            $text = '';
            
            foreach ($phpWord->getSections() as $section) {
                foreach ($section->getElements() as $element) {
                    $text .= $this->extractTextFromWordElement($element);
                }
            }
            
            return $this->cleanText($text);
        } catch (Exception $e) {
            throw new Exception("Failed to extract text from Word document: " . $e->getMessage());
        }
    }

    /**
     * Extract text from Word document elements recursively
     */
    private function extractTextFromWordElement($element): string
    {
        $text = '';
        
        if (method_exists($element, 'getElements')) {
            foreach ($element->getElements() as $childElement) {
                $text .= $this->extractTextFromWordElement($childElement);
            }
        }
        
        if (method_exists($element, 'getText')) {
            $text .= $element->getText() . ' ';
        }
        
        if (method_exists($element, 'getTextContent')) {
            $text .= $element->getTextContent() . ' ';
        }
        
        return $text;
    }

    /**
     * Extract text from PDF documents
     */
    private function extractFromPdf(string $filePath): string
    {
        try {
            $parser = new PdfParser();
            $pdf = $parser->parseFile($filePath);
            $text = $pdf->getText();
            
            if (empty(trim($text))) {
                throw new Exception("No text could be extracted from PDF. The document may be image-based or corrupted.");
            }
            
            return $this->cleanText($text);
        } catch (Exception $e) {
            throw new Exception("Failed to extract text from PDF: " . $e->getMessage());
        }
    }

    /**
     * Extract text from PowerPoint presentations (.pptx)
     */
    private function extractFromPowerPoint(string $filePath): string
    {
        try {
            $presentation = PresentationIOFactory::load($filePath);
            $text = '';
            
            foreach ($presentation->getAllSlides() as $slide) {
                foreach ($slide->getShapeCollection() as $shape) {
                    if (method_exists($shape, 'getTextContent')) {
                        $text .= $shape->getTextContent() . "\n";
                    }
                    
                    if (method_exists($shape, 'getRichTextElements')) {
                        foreach ($shape->getRichTextElements() as $element) {
                            if (method_exists($element, 'getText')) {
                                $text .= $element->getText() . ' ';
                            }
                        }
                    }
                }
            }
            
            return $this->cleanText($text);
        } catch (Exception $e) {
            throw new Exception("Failed to extract text from PowerPoint: " . $e->getMessage());
        }
    }

    /**
     * Clean and normalize extracted text
     */
    private function cleanText(string $text): string
    {
        // Remove excessive whitespace
        $text = preg_replace('/\s+/', ' ', $text);
        
        // Remove special characters that might cause issues
        $text = preg_replace('/[^\p{L}\p{N}\s\p{P}]/u', '', $text);
        
        // Trim and ensure proper spacing
        $text = trim($text);
        
        // Remove empty lines and normalize line breaks
        $text = preg_replace('/\n\s*\n/', "\n\n", $text);
        
        return $text;
    }

    /**
     * Validate if file can be processed
     */
    public function canProcess(string $fileType): bool
    {
        return in_array(strtolower($fileType), ['docx', 'pdf', 'pptx']);
    }

    /**
     * Get supported file types
     */
    public function getSupportedTypes(): array
    {
        return ['docx', 'pdf', 'pptx'];
    }

    /**
     * Estimate processing time based on file size
     */
    public function estimateProcessingTime(int $fileSizeBytes): int
    {
        // Basic estimation: ~1 second per MB
        $sizeInMB = $fileSizeBytes / 1024 / 1024;
        return max(5, (int)($sizeInMB * 1.5)); // Minimum 5 seconds
    }
}