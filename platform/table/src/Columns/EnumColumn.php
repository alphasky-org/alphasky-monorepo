<?php

namespace Alphasky\Table\Columns;

use BackedEnum;
use Alphasky\Base\Supports\Enum;
use Alphasky\Table\Contracts\FormattedColumn as FormattedColumnContract;
use Throwable;

class EnumColumn extends FormattedColumn implements FormattedColumnContract
{
    /**
     * @var string|BackedEnum|Enum
     */
    protected $enum;

    public static function make(array|string $data = [], string $name = ''): static
    {
        return parent::make($data, $name)
            ->alignCenter()
            ->width(100)
            ->withEmptyState()
            ->renderUsing(function (EnumColumn $column, $value) {
                try {
                    return $column->formattedValue($value);
                } catch (Throwable) {
                    return $value;
                }
            });
    }

    public function formattedValue($value): ?string
    {
        if (! $this->enum) {
            return '';
        }

        $enumClass = is_string($this->enum) ? $this->enum : $this->enum::class;

        return $enumClass::toVal($value);
    }

    public function enum(string|BackedEnum|Enum $enum): static
    {
        $this->enum = $enum;

        return $this;
    }
}
