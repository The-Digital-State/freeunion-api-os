<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\LibraryItemResource;
use App\Models\LibraryItem;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use Throwable;

class UploadController extends Controller
{
    public function __invoke(Request $request): AnonymousResourceCollection
    {
        $storage = Storage::disk(config('filesystems.public'));
        /** @var Collection<int, UploadedFile> */
        $uploadedFiles = new Collection();
        /** @var Collection<int, LibraryItem> */
        $uploaded = new Collection();

        foreach ($request->all() as $items) {
            if (! is_array($items)) {
                $items = [$items];
            }

            foreach ($items as $item) {
                if ($item instanceof UploadedFile) {
                    $uploadedFiles->push($item);

                    continue;
                }

                preg_match('#^data:(.*?);base64,(.*?)$#', mb_substr($item, 0, 256), $matches);
                $tmpFilePath = tempnam(sys_get_temp_dir(), 'uploaded');
                $mimeType = null;

                if ($tmpFilePath === false) {
                    continue;
                }

                if (empty($matches)) {
                    $originalName = pathinfo($item, PATHINFO_FILENAME).'.'.pathinfo($item, PATHINFO_EXTENSION);

                    try {
                        $response = (new Client())->get($item);
                        file_put_contents($tmpFilePath, $response->getBody()->getContents());

                        $mimeType = $response->getHeader('Content-Type')[0] ?? null;
                    } catch (Throwable) {
                    }
                } else {
                    $originalName = '';
                    $mimeType = $matches[1];
                    $decoded = base64_decode(mb_substr($item, mb_strlen($matches[1]) + 13));

                    if ($decoded) {
                        file_put_contents($tmpFilePath, $decoded);
                    }
                }

                if (file_exists($tmpFilePath) && filesize($tmpFilePath)) {
                    $uploadedFiles->push(new UploadedFile($tmpFilePath, $originalName, $mimeType));
                }
            }
        }

        /** @var UploadedFile $file */
        foreach ($uploadedFiles as $file) {
            $hash = sha1_file($file->getPathname());

            if ($hash === false) {
                continue;
            }

            $split = mb_str_split($hash, 2);
            $hashPath = implode('/', [...array_slice($split, 0, 4), implode('', array_slice($split, 4))]);
            $filePath = 'media/'.$hashPath;

            if ($extension = $file->guessExtension()) {
                $filePath .= '.'.$extension;
            }

            $libraryData = [
                'file_name' => $file->getClientOriginalName() ?: null,
                'mime_type' => $file->getMimeType(),
                'name' => $filePath,
                'size' => $file->getSize(),
            ];

            if ($storage->exists($filePath)) {
                $uploaded->push(LibraryItem::create($libraryData));
            } elseif ($storage->put($filePath, $file->getContent())) {
                $uploaded->push(LibraryItem::create($libraryData));

                if ($libraryData['mime_type'] && str_starts_with($libraryData['mime_type'], 'image/')) {
                    try {
                        $image = Image::make($file);
                        $image->fit(100, 100);
                        $storage->put("media/thumb/$hashPath.jpg", $image->stream('jpg'));
                    } catch (Throwable) {
                    }
                }
            }
        }

        return LibraryItemResource::collection($uploaded);
    }
}
