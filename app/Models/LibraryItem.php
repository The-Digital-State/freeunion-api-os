<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Class LibraryItem
 *
 * @property int $id
 * @property string $uuid
 * @property string|null $file_name
 * @property string|null $mime_type
 * @property string $name
 * @property int $size
 * @property Carbon|null $created_at
 * @property Collection<int, LibraryLink> $libraryLinks
 */
class LibraryItem extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['file_name', 'mime_type', 'name', 'size'];

    public function getUrl(): string
    {
        $storage = Storage::disk(
            str_starts_with($this->name, 'private') ? config('filesystems.private') : config('filesystems.public')
        );

        return $storage->url($this->name);
    }

    public function getThumb(): string|null
    {
        if ($this->mime_type && str_starts_with($this->mime_type, 'image/')) {
            $storage = Storage::disk(config('filesystems.public'));
            [, $path] = explode('/', $this->name, 2);
            $path = pathinfo($path, PATHINFO_DIRNAME).'/'.pathinfo($path, PATHINFO_FILENAME).'.jpg';

            if ($storage->exists("media/thumb/$path")) {
                return $storage->url("media/thumb/$path");
            }
        }

        return null;
    }

    /**
     * @return HasMany<LibraryLink>
     */
    public function libraryLinks(): HasMany
    {
        return $this->hasMany(LibraryLink::class);
    }

    public static function findByUuid(string $uuid): ?LibraryItem
    {
        return self::where('uuid', $uuid)->first();
    }

    protected static function boot(): void
    {
        parent::boot();

        self::creating(static function (LibraryItem $libraryItem) {
            $libraryItem->uuid = Str::uuid()->toString();
        });
    }
}
