<?php

namespace Alphasky\Page\Http\Controllers;

use Alphasky\Base\Http\Controllers\BaseController;
use Alphasky\Page\Models\Page;
use Alphasky\Page\Services\PageService;
use Alphasky\Slug\Facades\SlugHelper;
use Alphasky\Theme\Events\RenderingSingleEvent;
use Alphasky\Theme\Facades\Theme;

class PublicController extends BaseController
{
    public function getPage(string $slug, PageService $pageService)
    {
        $slug = SlugHelper::getSlug($slug, SlugHelper::getPrefix(Page::class));

        abort_unless($slug, 404);

        $data = $pageService->handleFrontRoutes($slug);

        if (isset($data['slug']) && $data['slug'] !== $slug->key) {
            return redirect()->to(url(SlugHelper::getPrefix(Page::class) . '/' . $data['slug']));
        }

        event(new RenderingSingleEvent($slug));

        return Theme::scope($data['view'], $data['data'], $data['default_view'])->render();
    }
}
