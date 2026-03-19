<?php

namespace Alphasky\ACL\Enums;

use Alphasky\Base\Facades\Html;
use Alphasky\Base\Supports\Enum;
use Illuminate\Support\HtmlString;

/**
 * @method static UserStatusEnum ACTIVATED()
 * @method static UserStatusEnum DEACTIVATED()
 */
class UserStatusEnum extends Enum
{
    public const ACTIVATED = 'activated';
    public const DEACTIVATED = 'deactivated';

    public static $langPath = 'core/acl::users.statuses';

    public function toHtml(): HtmlString|string
    {
        return match ($this->value) {
            self::ACTIVATED => Html::tag('span', self::ACTIVATED()->label(), ['class' => 'badge bg-info text-info-fg']),
            self::DEACTIVATED => Html::tag('span', self::DEACTIVATED()->label(), ['class' => 'badge bg-warning text-warning-fg']),
            default => parent::toHtml(),
        };
    }
}
