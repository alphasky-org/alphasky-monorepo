<?php

namespace Alphasky\Table\Contracts;

use Alphasky\Base\Contracts\BaseModel;
use Alphasky\Table\Abstracts\TableAbstract;
use stdClass;

interface FormattedColumn
{
    public function formattedValue($value): ?string;

    public function renderCell(BaseModel|stdClass|array $item, TableAbstract $table): string;
}
