<?php

namespace Alphasky\Base\Repositories\Eloquent;

use Alphasky\Base\Models\BaseModel;
use Alphasky\Base\Models\BaseQueryBuilder;
use Alphasky\Base\Models\MetaBox;
use Alphasky\Base\Repositories\Interfaces\MetaBoxInterface;
use Alphasky\Support\Repositories\Eloquent\RepositoriesAbstract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class MetaBoxRepository extends RepositoriesAbstract implements MetaBoxInterface
{
    public function __construct(protected BaseModel|BaseQueryBuilder|Builder|Model $model)
    {
        parent::__construct(new MetaBox());
    }
}
