<?php

namespace Alphasky\Base\Forms\FieldOptions;

use Closure;
use Alphasky\Base\Forms\FormFieldOptions;

class AudioFieldOption extends FormFieldOptions
{
    protected array|float|string|bool|null $value;

    protected int $maxDuration = 300; // 5 minutes in seconds

    protected string $audioFormat = 'audio/webm';

    protected bool $showWaveform = true;

    protected bool $showClearButton = true;

    protected bool $showDownloadButton = true;

    protected string $recordButtonColor = '#dc3545';

    protected string $playButtonColor = '#28a745';

    public function value(array|float|string|bool|null|Closure $value): static
    {
        $this->value = $value instanceof Closure ? $value() : $value;

        return $this;
    }

    public function getValue(): array|string|bool|null
    {
        return $this->value;
    }

    public function maxDuration(int $seconds): static
    {
        $this->maxDuration = $seconds;

        return $this;
    }

    public function getMaxDuration(): int
    {
        return $this->maxDuration;
    }

    public function audioFormat(string $format): static
    {
        $this->audioFormat = $format;

        return $this;
    }

    public function getAudioFormat(): string
    {
        return $this->audioFormat;
    }

    public function showWaveform(bool $show = true): static
    {
        $this->showWaveform = $show;

        return $this;
    }

    public function getShowWaveform(): bool
    {
        return $this->showWaveform;
    }

    public function showClearButton(bool $show = true): static
    {
        $this->showClearButton = $show;

        return $this;
    }

    public function getShowClearButton(): bool
    {
        return $this->showClearButton;
    }

    public function showDownloadButton(bool $show = true): static
    {
        $this->showDownloadButton = $show;

        return $this;
    }

    public function getShowDownloadButton(): bool
    {
        return $this->showDownloadButton;
    }

    public function recordButtonColor(string $color): static
    {
        $this->recordButtonColor = $color;

        return $this;
    }

    public function getRecordButtonColor(): string
    {
        return $this->recordButtonColor;
    }

    public function playButtonColor(string $color): static
    {
        $this->playButtonColor = $color;

        return $this;
    }

    public function getPlayButtonColor(): string
    {
        return $this->playButtonColor;
    }

    public function getAttributes(): array
    {
        return parent::getAttributes();
    }

    public function getDefaultAttributes(): array
    {
        return array_merge(parent::getDefaultAttributes(), [
            'data-max-duration' => $this->getMaxDuration(),
            'data-audio-format' => $this->getAudioFormat(),
            'data-show-waveform' => $this->getShowWaveform() ? 'true' : 'false',
            'data-show-clear-button' => $this->getShowClearButton() ? 'true' : 'false',
            'data-show-download-button' => $this->getShowDownloadButton() ? 'true' : 'false',
            'data-record-button-color' => $this->getRecordButtonColor(),
            'data-play-button-color' => $this->getPlayButtonColor(),
        ]);
    }

    public function toArray(): array
    {
        $data = parent::toArray();
        
        $data['max_duration'] = $this->getMaxDuration();
        $data['audio_format'] = $this->getAudioFormat();
        $data['show_waveform'] = $this->getShowWaveform();
        $data['show_clear_button'] = $this->getShowClearButton();
        $data['show_download_button'] = $this->getShowDownloadButton();
        $data['record_button_color'] = $this->getRecordButtonColor();
        $data['play_button_color'] = $this->getPlayButtonColor();
        
        return $data;
    }
}