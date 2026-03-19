<?php

namespace Alphasky\Media\Http\Controllers;

use Alphasky\Base\Http\Controllers\BaseController;
use Alphasky\Media\Facades\RvMedia;
use Alphasky\Media\Models\MediaFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;

class PublicMediaController extends BaseController
{
    public function show(string $hash, string $id)
    {
        $originId = MediaFile::isUsingStringId() ? $id : hexdec($id);

        abort_if(sha1($id) !== $hash, 404);

        $mediaFile = MediaFile::query()
            ->whereKey($originId)
            ->firstOrFail();

        if ($mediaFile->visibility === 'private') {
            return response()->download(Storage::disk('local')->path($mediaFile->url));
        }

        $response = Http::withoutVerifying()->get(RvMedia::url($mediaFile->url));

        abort_if($response->failed(), 403, $response->reason());

        $body = $response->toPsrResponse()->getBody();

        return Response::streamDownload(function () use ($body): void {
            while (! $body->eof()) {
                echo $body->read(1024);
            }
        }, headers: $response->headers());
    }
}
