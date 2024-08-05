<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Class LibraryLink
 *
 * @property int $id
 * @property string $collection_name
 * @property int $order
 * @property LibraryItem $libraryItem
 */
class LibraryLink extends Model
{
    public $timestamps = false;

    protected $fillable = ['library_item_id', 'collection_name', 'order'];

    /**
     * @phpstan-ignore-next-line
     */
    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<LibraryItem, LibraryLink>
     */
    public function libraryItem(): BelongsTo
    {
        return $this->belongsTo(LibraryItem::class);
    }

    protected static function boot(): void
    {
        parent::boot();

        self::creating(static function (LibraryLink $libraryLink) {
            if (
                $libraryLink->libraryItem->created_at
                && $libraryLink->libraryItem->created_at->isBefore(now()->subHour())
            ) {
                return false;
            }

            return true;
        });
    }
}
