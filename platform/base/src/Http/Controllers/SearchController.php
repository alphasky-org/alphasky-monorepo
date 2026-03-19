<?php

namespace Alphasky\Base\Http\Controllers;

use Alphasky\Base\Contracts\GlobalSearchableManager;
use Alphasky\Base\Http\Responses\BaseHttpResponse;
use Illuminate\Http\Request;

class SearchController extends BaseController
{
    public function __invoke(GlobalSearchableManager $manager, Request $request): BaseHttpResponse
    {
        $request->validate(['keyword' => ['nullable', 'string', 'max:1024']]);

        $results = $manager->search((string) $request->input('keyword'));

        return $this
            ->httpResponse()
            ->setData(view('core/base::global-search.index', compact('results'))->render());
    }
}
