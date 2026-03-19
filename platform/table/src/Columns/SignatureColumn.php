<?php

namespace Alphasky\Table\Columns;

use Alphasky\Base\Facades\Html;
use Alphasky\Media\Facades\RvMedia;
use Alphasky\Table\Contracts\FormattedColumn as FormattedColumnContract;

class SignatureColumn extends FormattedColumn implements FormattedColumnContract
{
    protected int $width = 80;

    protected int $height = 40;

    protected bool $showBorder = true;

    protected bool $showPlaceholder = true;

    protected string $placeholderText = 'لا يوجد توقيع';

    public static function make(array|string $data = [], string $name = ''): static
    {
        return parent::make($data ?: 'signature', $name)
            ->title(trans('core/base::tables.signature'))
            ->orderable(false)
            ->searchable(false)
            ->width(100);
    }

    public function signatureWidth(int $width): static
    {
        $this->width = $width;

        return $this;
    }

    public function signatureHeight(int $height): static
    {
        $this->height = $height;

        return $this;
    }

    public function signatureSize(int $width, int $height): static
    {
        $this->width = $width;
        $this->height = $height;

        return $this;
    }

    public function showBorder(bool $show = true): static
    {
        $this->showBorder = $show;

        return $this;
    }

    public function showPlaceholder(bool $show = true): static
    {
        $this->showPlaceholder = $show;

        return $this;
    }

    public function placeholderText(string $text): static
    {
        $this->placeholderText = $text;

        return $this;
    }

    public function formattedValue($value): string
    {
        $table = $this->getTable();

        // Handle exports
        if ($table->request()->has('action')) {
            if ($table->isExportingToCSV()) {
                return $this->hasSignature($value) ? 'نعم' : 'لا';
            }

            if ($table->isExportingToExcel()) {
                return $this->hasSignature($value) ? 'موقع' : 'غير موقع';
            }
        }

        return $this->renderSignatureHtml($value);
    }

    protected function hasSignature(?string $value): bool
    {
        if (empty($value) || is_null($value) || trim($value) === '') {
            return false;
        }
        
        // Check if it's a valid base64 data URL
        if (strpos($value, 'data:image') === 0) {
            // Extract base64 part
            $base64Data = explode(',', $value, 2);
            if (count($base64Data) === 2) {
                // Check if base64 data is valid and not truncated
                $decodedData = base64_decode($base64Data[1], true);
                return $decodedData !== false && strlen($base64Data[1]) > 100; // Minimum length check
            }
        }
        
        return true; // For file paths or other formats
    }

    protected function renderSignatureHtml(?string $value): string
    {
        if (!$this->hasSignature($value)) {
            return $this->renderPlaceholder();
        }

        // Check if it's a file path first (new file storage system)
        if (!str_starts_with($value, 'data:image') && (str_contains($value, '/') || str_contains($value, '\\'))) {
            return $this->renderFileSignature($value);
        }

        // Check if it's a base64 data URL (legacy system)
        if (str_starts_with($value, 'data:image')) {
            // Validate base64 data
            $base64Parts = explode(',', $value, 2);
            if (count($base64Parts) !== 2) {
                return $this->renderErrorPlaceholder('تنسيق التوقيع غير صحيح');
            }
            
            $base64Data = $base64Parts[1];
            $decodedData = base64_decode($base64Data, true);
            
            if ($decodedData === false || strlen($base64Data) < 100) {
                return $this->renderErrorPlaceholder('بيانات التوقيع تالفة');
            }
            
            return $this->renderBase64Signature($value);
        }

        // Fallback: treat as base64 without data URL prefix (legacy)
        if (strlen($value) > 100) {
            $decodedData = base64_decode($value, true);
            if ($decodedData !== false) {
                return $this->renderBase64Signature('data:image/png;base64,' . $value);
            }
        }
        
        return $this->renderErrorPlaceholder('تنسيق التوقيع غير مدعوم');
    }

    protected function renderBase64Signature(string $dataUrl): string
    {
        $style = $this->getImageStyle();
        
        // Add debugging info as data attributes
        $base64Parts = explode(',', $dataUrl, 2);
        $base64Length = count($base64Parts) === 2 ? strlen($base64Parts[1]) : 0;
        
        return sprintf(
            '<img src="%s" alt="التوقيع" style="%s" class="signature-preview-table" title="اضغط للتكبير - الحجم: %d حرف" onclick="showSignatureModal(this)" data-signature-length="%d" data-signature-valid="%s">',
            htmlspecialchars($dataUrl),
            $style,
            $base64Length,
            $base64Length,
            $base64Length > 100 ? 'true' : 'false'
        );
    }

    protected function renderFileSignature(string $filePath): string
    {
        // Check if file exists in storage
        if (\Storage::disk('public')->exists($filePath)) {
            $imageUrl = \Storage::disk('public')->url($filePath);
        } else {
            // Fallback to RvMedia for compatibility
            $imageUrl = RvMedia::getImageUrl($filePath, 'thumb', false, RvMedia::getDefaultImage());
        }
        
        $style = $this->getImageStyle();
        $downloadUrl = $imageUrl;
        
        return sprintf(
            '<div class="signature-file-preview">
                <img src="%s" alt="التوقيع" style="%s" class="signature-preview-table" title="اضغط للتكبير" onclick="showSignatureModal(this)">
               
            </div>',
            htmlspecialchars($imageUrl),
            $style,
            htmlspecialchars($downloadUrl)
        );
    }

    protected function renderPlaceholder(): string
    {
        if (!$this->showPlaceholder) {
            return '';
        }

        return sprintf(
            '<span class="text-muted signature-placeholder" style="font-size: 12px; display: inline-block; width: %dpx; text-align: center;">%s</span>',
            $this->width,
            htmlspecialchars($this->placeholderText)
        );
    }

    protected function renderErrorPlaceholder(string $errorMessage): string
    {
        return sprintf(
            '<span class="text-danger signature-error-placeholder" style="font-size: 11px; display: inline-block; width: %dpx; text-align: center; padding: 5px;" title="%s">
                <i class="fas fa-exclamation-triangle"></i><br>
                خطأ في التوقيع
            </span>',
            $this->width,
            htmlspecialchars($errorMessage)
        );
    }

    protected function getImageStyle(): string
    {
        $borderStyle = $this->showBorder ? 'border: 1px solid #dee2e6; border-radius: 4px;' : '';
        
        return sprintf(
            'width: %dpx; height: %dpx; object-fit: contain; cursor: pointer; %s background: white; padding: 2px;',
            $this->width,
            $this->height,
            $borderStyle
        );
    }

  
}