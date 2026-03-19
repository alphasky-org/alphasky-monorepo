<?php

namespace Alphasky\Api\Models;

use Alphasky\Base\Contracts\BaseModel;
use Alphasky\Base\Models\Concerns\HasBaseEloquentBuilder;
use Alphasky\Base\Models\Concerns\HasMetadata;
use Alphasky\Base\Models\Concerns\HasUuidsOrIntegerIds;
use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;

class PersonalAccessToken extends SanctumPersonalAccessToken implements BaseModel
{
    use HasMetadata;
    use HasUuidsOrIntegerIds;
    use HasBaseEloquentBuilder;
}
