<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ImageProcessingService
{
    /**
     * Validate image file
     */
    public function validateImageFile(UploadedFile $file): bool
    {
        // Check file size (max 10MB)
        if ($file->getSize() > 10 * 1024 * 1024) {
            Log::warning('Image file too large: ' . $file->getSize());
            return false;
        }

        // Check file type
        $allowedTypes = ['image/jpeg', 'image/png', 'image/bmp', 'image/tiff', 'image/gif'];
        if (!in_array($file->getMimeType(), $allowedTypes)) {
            Log::warning('Invalid image type: ' . $file->getMimeType());
            return false;
        }

        return true;
    }

    /**
     * Process uploaded image for OCR
     */
    public function processUploadedImage(UploadedFile $file): ?string
    {
        try {
            if (!$this->validateImageFile($file)) {
                return null;
            }

            // Store file temporarily
            $filePath = $file->store('temp-images', 'public');
            $fullPath = Storage::disk('public')->path($filePath);

            // Try to extract text using OCR
            $extractedText = $this->extractTextFromImage($fullPath);

            // Clean up temporary file
            Storage::disk('public')->delete($filePath);

            return $extractedText;

        } catch (\Exception $e) {
            Log::error('Image processing error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Extract text from image using OCR
     */
    protected function extractTextFromImage(string $imagePath): ?string
    {
        try {
            // Check if Tesseract is available
            if (!$this->isTesseractAvailable()) {
                Log::warning('Tesseract OCR not available - returning placeholder text');
                return $this->getPlaceholderText();
            }

            // Use Tesseract to extract text
            $command = sprintf(
                'tesseract "%s" stdout -l eng 2>/dev/null',
                escapeshellarg($imagePath)
            );

            $output = shell_exec($command);
            
            if (empty($output)) {
                Log::warning('No text extracted from image');
                return $this->getPlaceholderText();
            }

            // Clean up the extracted text
            $text = trim($output);
            $text = preg_replace('/\s+/', ' ', $text);
            
            if (strlen($text) < 10) {
                Log::warning('Extracted text too short, using placeholder');
                return $this->getPlaceholderText();
            }

            return $text;

        } catch (\Exception $e) {
            Log::error('OCR extraction error: ' . $e->getMessage());
            return $this->getPlaceholderText();
        }
    }

    /**
     * Check if Tesseract OCR is available
     */
    protected function isTesseractAvailable(): bool
    {
        $output = shell_exec('tesseract --version 2>/dev/null');
        return !empty($output);
    }

    /**
     * Get placeholder text when OCR is not available
     */
    protected function getPlaceholderText(): string
    {
        return "Image uploaded successfully. However, OCR (Optical Character Recognition) is not currently available on this server. To extract text from images, please install Tesseract OCR. For now, you can use the text input or upload a PDF document instead.";
    }

    /**
     * Get supported image formats
     */
    public function getSupportedFormats(): array
    {
        return [
            'JPEG/JPG' => 'image/jpeg',
            'PNG' => 'image/png',
            'BMP' => 'image/bmp',
            'TIFF' => 'image/tiff',
            'GIF' => 'image/gif',
        ];
    }

    /**
     * Get maximum file size
     */
    public function getMaxFileSize(): int
    {
        return 10 * 1024 * 1024; // 10MB
    }

    /**
     * Get file size in human readable format
     */
    public function getMaxFileSizeFormatted(): string
    {
        return '10 MB';
    }
}