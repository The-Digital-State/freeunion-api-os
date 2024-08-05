<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * Class Banner
 *
 * @property int $id
 * @property int|null $organization_id
 * @property string|null $large
 * @property string|null $small
 * @property int $index
 * @property bool $enabled
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Organization|null $organization
 */
class Banner extends Model
{
    protected $fillable = [
        'organization_id', 'large', 'small', 'index', 'enabled',
    ];

    protected $casts = [
        'index' => 'integer',
        'enabled' => 'boolean',
    ];

    public function getLarge(): string
    {
        if ($this->large) {
            /**
             * @var FilesystemAdapter $storage
             */
            $storage = Storage::disk(config('filesystems.public'));

            return $storage->url("bims/$this->large");
        }

        return '';
    }

    public function getSmall(): string
    {
        if ($this->small) {
            /**
             * @var FilesystemAdapter $storage
             */
            $storage = Storage::disk(config('filesystems.public'));

            return $storage->url("bims/$this->small");
        }

        return '';
    }

    /**
     * @return BelongsTo<Organization, Banner>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
