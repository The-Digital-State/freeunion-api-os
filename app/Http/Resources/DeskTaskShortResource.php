<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\DeskTask;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/** @mixin DeskTask */
class DeskTaskShortResource extends JsonResource
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
        $image = $this->deskImages->count() > 0 ? $this->deskImages->pluck('image')[0] : null;

        return [
            'id' => $this->id,
            'user' => new UserShortResource($this->user),
            'suggestion_id' => $this->suggestion?->id,
            'column_id' => $this->column_id,
            'index' => $this->index,
            'title' => $this->title,
            'description' => $this->description,
            'checklist' => $this->checklist,
            'visibility' => $this->visibility,
            'can_self_assign' => $this->can_self_assign,
            'is_urgent' => $this->is_urgent,
            'image' => $image === null ? null : $storage->url("org_$this->organization_id/desk/$image"),
            'comments_count' => $this->deskComments->count(),
            'images_count' => $this->deskImages->count(),
            'users' => UserShortResource::collection($this->users),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
