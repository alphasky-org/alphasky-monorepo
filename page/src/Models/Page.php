<?php

namespace Alphasky\Page\Models;

use Alphasky\ACL\Models\User;
use Alphasky\Base\Casts\SafeContent;
use Alphasky\Base\Enums\BaseStatusEnum;
use Alphasky\Base\Models\BaseModel;
use Alphasky\Revision\RevisionableTrait;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Page extends BaseModel
{
    use RevisionableTrait;

    protected $table = 'pages';

    protected bool $revisionEnabled = true;

    protected bool $revisionCleanup = true;

    protected int $historyLimit = 20;

    protected array $dontKeepRevisionOf = ['content'];

    protected $fillable = [
        'name',
        'content',
        'image',
        'template',
        'description',
        'status',
        'user_id',
    ];

    protected $casts = [
        'status' => BaseStatusEnum::class,
        'name' => SafeContent::class,
        'description' => SafeContent::class,
        'template' => SafeContent::class,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withDefault();
    }

    protected static function booted(): void
    {
        static::creating(function (self $page): void {
            $page->user_id = $page->user_id ?: auth()->id();
        });
    }
}
