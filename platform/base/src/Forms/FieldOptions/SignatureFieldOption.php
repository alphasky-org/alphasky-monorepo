<?php

namespace Alphasky\Base\Forms\FieldOptions;

use Closure;
use Alphasky\Base\Forms\FormFieldOptions;

class SignatureFieldOption extends FormFieldOptions
{
    protected array|float|string|bool|null $value;

    protected string $signaturePadColor = '#000000';

    protected int $signaturePadWidth = 500;

    protected int $signaturePadHeight = 200;

    protected bool $clearButton = true;

    public function value(array|float|string|bool|null|Closure $value): static
    {
        $this->value = $value instanceof Closure ? $value() : $value;

        return $this;
    }

    public function getValue(): array|string|bool|null
    {
        return $this->value;
    }

    public function signaturePadColor(string $color): static
    {
        $this->signaturePadColor = $color;

        return $this;
    }

    public function getSignaturePadColor(): string
    {
        return $this->signaturePadColor;
    }

    public function signaturePadWidth(int $width): static
    {
        $this->signaturePadWidth = $width;

        return $this;
    }

    public function getSignaturePadWidth(): int
    {
        return $this->signaturePadWidth;
    }

    public function signaturePadHeight(int $height): static
    {
        $this->signaturePadHeight = $height;

        return $this;
    }

    public function getSignaturePadHeight(): int
    {
        return $this->signaturePadHeight;
    }

    public function clearButton(bool $show = true): static
    {
        $this->clearButton = $show;

        return $this;
    }

    public function showClearButton(): bool
    {
        return $this->clearButton;
    }

    public function getAttributes(): array
    {
        return parent::getAttributes();
    }

    public function getDefaultAttributes(): array
    {
        return array_merge(parent::getDefaultAttributes(), [
            'data-signature-pad-color' => $this->getSignaturePadColor(),
            'data-signature-pad-width' => $this->getSignaturePadWidth(),
            'data-signature-pad-height' => $this->getSignaturePadHeight(),
            'data-show-clear-button' => $this->showClearButton() ? 'true' : 'false',
        ]);
    }

    public function toArray(): array
    {
        $data = parent::toArray();
        
        $data['signature_pad_color'] = $this->getSignaturePadColor();
        $data['signature_pad_width'] = $this->getSignaturePadWidth();
        $data['signature_pad_height'] = $this->getSignaturePadHeight();
        $data['show_clear_button'] = $this->showClearButton();
        
        return $data;
    }
}