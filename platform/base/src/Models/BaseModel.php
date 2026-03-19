<?php

namespace Alphasky\Base\Models;

use Alphasky\Base\Contracts\BaseModel as BaseModelContract;
use Alphasky\Base\Facades\MacroableModels;
use Alphasky\Base\Models\Concerns\HasBaseEloquentBuilder;
use Alphasky\Base\Models\Concerns\HasMetadata;
use Alphasky\Base\Models\Concerns\HasUuidsOrIntegerIds;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * @method static \Alphasky\Base\Models\BaseQueryBuilder query()
 */
class BaseModel extends Model implements BaseModelContract
{
    use HasBaseEloquentBuilder;
    use HasMetadata;
    use HasUuidsOrIntegerIds;

    public function __get($key)
    {
        if (MacroableModels::modelHasMacro(static::class, $method = 'get' . Str::studly($key) . 'Attribute')) {
            return $this->{$method}();
        }

        return parent::__get($key);
    }
}
