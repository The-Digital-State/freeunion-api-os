<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\DeskImage;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/** @mixin DeskImage */
class DeskImageResource extends JsonResource
{
    /**
     * @param  Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        /**
         * @var FilesystemAdapter $storage
         */
        $storage = Storage::disk(config('filesystems.public'));
        $orgId = $this->deskTask->organization_id;

        return [
            'id' => $this->id,
            'user_id' => new UserShortResource($this->user),
            'image' => $storage->url("org_$orgId/desk/$this->image"),
            'created_at' => $this->created_at,
        ];
    }
}
