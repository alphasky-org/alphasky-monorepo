<?php

namespace Alphasky\SeoHelper\Entities\OpenGraph;

use Alphasky\SeoHelper\Bases\MetaCollection as BaseMetaCollection;

class MetaCollection extends BaseMetaCollection
{
    protected $prefix = 'og:';

    protected $nameProperty = 'property';
}
