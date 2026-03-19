<?php

namespace Alphasky\Table\Columns;

use Alphasky\Base\Facades\Html;
use Alphasky\Media\Facades\RvMedia;
use Alphasky\Table\Contracts\FormattedColumn as FormattedColumnContract;

class AudioColumn extends FormattedColumn implements FormattedColumnContract
{
    protected int $width = 120;

    protected int $height = 40;

    protected bool $showControls = true;

    protected bool $showPlaceholder = true;

    protected string $placeholderText = 'لا يوجد تسجيل صوتي';

    public static function make(array|string $data = [], string $name = ''): static
    {
        return parent::make($data ?: 'audio', $name)
            ->title(trans('core/base::tables.audio'))
            ->orderable(false)
            ->searchable(false)
            ->width(150);
    }

    public function audioWidth(int $width): static
    {
        $this->width = $width;

        return $this;
    }

    public function audioHeight(int $height): static
    {
        $this->height = $height;

        return $this;
    }

    public function audioSize(int $width, int $height): static
    {
        $this->width = $width;
        $this->height = $height;

        return $this;
    }

    public function showControls(bool $show = true): static
    {
        $this->showControls = $show;

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
                return $this->hasAudio($value) ? 'نعم' : 'لا';
            }

            if ($table->isExportingToExcel()) {
                return $this->hasAudio($value) ? 'يحتوي على تسجيل' : 'لا يحتوي على تسجيل';
            }
        }

        return $this->renderAudioHtml($value);
    }

    protected function hasAudio(?string $value): bool
    {
        if (empty($value) || is_null($value) || trim($value) === '') {
            return false;
        }
        
        // Check if it's a valid base64 data URL for audio
        if (strpos($value, 'data:audio') === 0) {
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

    protected function renderAudioHtml(?string $value): string
    {
        if (!$this->hasAudio($value)) {
            return $this->renderErrorPlaceholder('بيانات التسجيل الصوتي غير صحيحة أو مقطوعة');
        }

        // Check if it's a base64 data URL
        if (strpos($value, 'data:audio') === 0) {
            // Validate base64 data
            $base64Parts = explode(',', $value, 2);
            if (count($base64Parts) !== 2) {
                return $this->renderErrorPlaceholder('تنسيق التسجيل الصوتي غير صحيح');
            }
            
            $base64Data = $base64Parts[1];
            $decodedData = base64_decode($base64Data, true);
            
            if ($decodedData === false || strlen($base64Data) < 100) {
                return $this->renderErrorPlaceholder('بيانات التسجيل الصوتي تالفة');
            }
            
            return $this->renderBase64Audio($value);
        }

        // Check if it's a file path
        if (strpos($value, '/') !== false || strpos($value, '\\') !== false) {
            return $this->renderFileAudio($value);
        }

        // Fallback: treat as base64 without data URL prefix
        if (strlen($value) > 100) {
            $decodedData = base64_decode($value, true);
            if ($decodedData !== false) {
                return $this->renderBase64Audio('data:audio/webm;base64,' . $value);
            }
        }
        
        return $this->renderErrorPlaceholder('تنسيق التسجيل الصوتي غير مدعوم');
    }

    protected function renderBase64Audio(string $dataUrl): string
    {
        $style = $this->getAudioStyle();
        
        // Add debugging info as data attributes
        $base64Parts = explode(',', $dataUrl, 2);
        $base64Length = count($base64Parts) === 2 ? strlen($base64Parts[1]) : 0;
        
        $controlsAttr = $this->showControls ? 'controls' : '';
        
        return sprintf(
            '<div class="audio-player-container" style="%s">
                <audio %s class="audio-preview-table" title="تسجيل صوتي - الحجم: %d حرف" data-audio-length="%d" data-audio-valid="%s" data-is-file="false">
                    <source src="%s" type="audio/webm">
                    <source src="%s" type="audio/wav">
                    <source src="%s" type="audio/mp4">
                    متصفحك لا يدعم تشغيل الصوت
                </audio>
                <div class="audio-controls">
                    <button type="button" class="btn btn-sm btn-primary play-pause-btn" onclick="toggleAudioPlayback(this)">
                        <i class="fas fa-play"></i>
                    </button>
                  
                </div>
            </div>',
            $style,
            $controlsAttr,
            $base64Length,
            $base64Length,
            $base64Length > 100 ? 'true' : 'false',
            htmlspecialchars($dataUrl),
            htmlspecialchars($dataUrl),
            htmlspecialchars($dataUrl)
        );
    }

    protected function renderFileAudio(string $filePath): string
    {
        // Check if file exists in storage
        $fullPath = public_path('storage/' . $filePath);
        $audioUrl = asset('storage/' . $filePath);
        
        $audioSize = 0;
        $isValidAudio = true;
        
        if (file_exists($fullPath)) {
            $audioSize = round(filesize($fullPath) / 1024, 2);
        } else {
            $isValidAudio = false;
        }
      
        if (!$isValidAudio) {
            return $this->renderErrorPlaceholder('ملف صوتي مفقود: ' . $filePath);
        }
        
        $style = $this->getAudioStyle();
        
        $controlsAttr = $this->showControls ? 'controls' : '';
        
        return sprintf(
            '<div class="audio-player-container">
                <audio %s class="audio-preview-table" title="تسجيل صوتي - الحجم: %.2f KB" data-audio-size="%.2f" data-audio-valid="true" data-is-file="true">
                    <source src="%s" type="audio/webm">
                    <source src="%s" type="audio/wav">
                    <source src="%s" type="audio/mp4">
                    متصفحك لا يدعم تشغيل الصوت
                </audio>
                <div class="audio-controls">
                    <button type="button" class="btn btn-sm btn-primary play-pause-btn" onclick="toggleAudioPlayback(this)">
                        <i class="fas fa-play"></i>
                    </button>
                                    
                </div>
            </div>',
            $style,
            $controlsAttr,
            $audioSize,
            $audioSize,
            htmlspecialchars($audioUrl),
            htmlspecialchars($audioUrl),
            htmlspecialchars($audioUrl),
            htmlspecialchars($audioUrl)
        );
    }

    protected function renderPlaceholder(): string
    {
        if (!$this->showPlaceholder) {
            return '';
        }

        return sprintf(
            '<span class="text-muted audio-placeholder" style="font-size: 12px; display: inline-block; width: %dpx; text-align: center;">%s</span>',
            $this->width,
            htmlspecialchars($this->placeholderText)
        );
    }

    protected function renderErrorPlaceholder(string $errorMessage): string
    {
        return sprintf(
            '<span class="text-danger audio-error-placeholder" style="font-size: 11px; display: inline-block; width: %dpx; text-align: center; padding: 5px;" title="%s">
                <i class="fas fa-exclamation-triangle"></i><br>
                خطأ في التسجيل
            </span>',
            $this->width,
            htmlspecialchars($errorMessage)
        );
    }

    protected function getAudioStyle(): string
    {
        return sprintf(
            'width: %dpx; height: %dpx; display: flex; align-items: center; gap: 5px;',
            $this->width,
            $this->height
        );
    }

    protected function getModalScript(): string
    {
        return '
        <!-- Modal for audio details -->
        <div class="modal fade" id="audioModal" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">تفاصيل التسجيل الصوتي</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body text-center">
                        <audio id="audioModalPlayer" controls class="w-100 mb-3">
                            <source id="audioModalSource" src="" type="audio/webm">
                            متصفحك لا يدعم تشغيل الصوت
                        </audio>
                        <div class="mt-2">
                            <small class="text-muted" id="audioModalInfo"></small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
                        <a id="audioDownload" href="#" class="btn btn-primary" download="audio.webm">تحميل التسجيل</a>
                    </div>
                </div>
            </div>
        </div>

        <script>
        function toggleAudioPlayback(button) {
            const audioContainer = button.closest(".audio-player-container");
            const audio = audioContainer.querySelector("audio");
            const icon = button.querySelector("i");
            
            if (audio.paused) {
                audio.play();
                icon.className = "fas fa-pause";
                button.classList.remove("btn-primary");
                button.classList.add("btn-warning");
            } else {
                audio.pause();
                icon.className = "fas fa-play";
                button.classList.remove("btn-warning");
                button.classList.add("btn-primary");
            }
            
            audio.addEventListener("ended", function() {
                icon.className = "fas fa-play";
                button.classList.remove("btn-warning");
                button.classList.add("btn-primary");
            });
        }

        function showAudioModal(button) {
            const audioContainer = button.closest(".audio-player-container");
            const audio = audioContainer.querySelector("audio");
            const source = audio.querySelector("source");
            
            const modal = new bootstrap.Modal(document.getElementById("audioModal"));
            const modalPlayer = document.getElementById("audioModalPlayer");
            const modalSource = document.getElementById("audioModalSource");
            const modalInfo = document.getElementById("audioModalInfo");
            const downloadLink = document.getElementById("audioDownload");
            
            // Set audio source
            modalSource.src = source.src;
            modalPlayer.load();
            downloadLink.href = source.src;
            
            // Set info
            const audioLength = audio.dataset.audioLength || "غير معروف";
            const isValid = audio.dataset.audioValid === "true" ? "صحيح" : "غير صحيح";
            modalInfo.innerHTML = `حجم البيانات: ${audioLength} حرف | الحالة: ${isValid}`;
            
            modal.show();
        }
        </script>
        ';
    }

    public function renderFooter(): string
    {
        static $scriptRendered = false;
        
        if (!$scriptRendered) {
            $scriptRendered = true;
            return $this->getModalScript();
        }
        
        return '';
    }
}