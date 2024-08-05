<?php

declare(strict_types=1);

namespace App\Models\Traits;

use App\Models\LibraryItem;
use App\Models\LibraryLink;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\DB;

trait HasMedia
{
    /**
     * @param  string  $collectionName
     * @return MorphMany<LibraryLink>
     */
    public function media(string $collectionName = 'default'): MorphMany
    {
        return $this->morphMany(LibraryLink::class, 'model')
            ->with('libraryItem')
            ->where('collection_name', $collectionName)
            ->orderBy('order');
    }

    /**
     * Get first media
     *
     * @param  string  $collectionName
     * @return LibraryLink|null
     */
    public function firstMedia(string $collectionName = 'default'): ?LibraryLink
    {
        return $this->media($collectionName)->first();
    }

    /**
     * Get last media
     *
     * @param  string  $collectionName
     * @return LibraryLink|null
     */
    public function lastMedia(string $collectionName = 'default'): ?LibraryLink
    {
        return $this->media($collectionName)->reorder('order', 'desc')->first();
    }

    /**
     * Add new media to the end without remove
     *
     * @param  array<LibraryItem|string>|LibraryItem|string  $uuid
     * @param  string  $collectionName
     * @return void
     */
    public function addMedia(array|LibraryItem|string $uuid, string $collectionName = 'default'): void
    {
        $lastOrder = $this->lastMedia($collectionName)?->order ?? -1;
        $existIds = $this->media($collectionName)->pluck('library_item_id');

        $this->normalizeItems($uuid)
            ->filter(fn (LibraryItem $item, int $key) => ! $existIds->contains($item->id))
            ->pluck('id')
            ->each(fn (int $itemId, int $key) => $this->media($collectionName)->firstOrNew([
                'library_item_id' => $itemId,
            ], [
                'collection_name' => $collectionName,
                'order' => $lastOrder + $key + 1,
            ])->save());
    }

    /**
     * Sync media with add/remove and rearrange
     *
     * @param  array<LibraryItem|string>|LibraryItem|string  $uuid
     * @param  string  $collectionName
     * @return void
     */
    public function updateMedia(array|LibraryItem|string $uuid, string $collectionName = 'default'): void
    {
        $items = $this->normalizeItems($uuid);

        DB::beginTransaction();

        $this->media($collectionName)
            ->whereNotIn('library_item_id', $items->pluck('id'))
            ->delete();

        $items->each(fn (LibraryItem $item, int $key) => $this->media($collectionName)->updateOrCreate([
            'library_item_id' => $item->id,
        ], [
            'collection_name' => $collectionName,
            'order' => $key,
        ])->save());

        DB::commit();
    }

    /**
     * Remove media
     *
     * @param  array<LibraryItem|string>|LibraryItem|string  $uuid
     * @param  string  $collectionName
     * @return void
     */
    public function removeMedia(array|LibraryItem|string $uuid, string $collectionName = 'default'): void
    {
        $this->media($collectionName)
            ->whereIn('library_item_id', $this->normalizeItems($uuid)->pluck('id'))
            ->delete();
    }

    /**
     * Remove all media
     *
     * @param  string  $collectionName
     * @return void
     */
    public function removeAllMedia(string $collectionName = 'default'): void
    {
        $this->media($collectionName)->delete();
    }

    /**
     * Normalize request variable
     *
     * @param  array<LibraryItem|string>|LibraryItem|string  $uuid
     * @return Collection<int, LibraryItem>
     */
    private function normalizeItems(array|LibraryItem|string $uuid): Collection
    {
        /** @var Collection<int, LibraryItem> */
        $items = new Collection();

        foreach (is_array($uuid) ? $uuid : [$uuid] as $item) {
            if ($item instanceof LibraryItem) {
                $items->push($item);
            } else {
                $findItem = LibraryItem::findByUuid($item);

                if ($findItem !== null) {
                    $items->push($findItem);
                }
            }
        }

        return $items->filter();
    }

    protected static function bootHasMedia(): void
    {
        static::deleting(static function (Model $model) {
            if (method_exists($model, 'removeAllMedia')) {
                $model->removeAllMedia();
            }
        });
    }
}
