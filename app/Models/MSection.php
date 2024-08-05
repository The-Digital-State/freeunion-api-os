<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * Class MSection
 *
 * @property int $id
 * @property int $organization_id
 * @property string $name
 * @property string|null $description
 * @property string|null $cover
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Collection<int, Material> $materials
 * @property Organization $organization
 */
class MSection extends Model
{
    protected $fillable = ['name', 'description', 'cover'];

    /**
     * @return HasMany<Material>
     */
    public function materials(): HasMany
    {
        return $this->hasMany(Material::class);
    }

    /**
     * @return BelongsTo<Organization, MSection>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function getCover(): string
    {
        if ($this->cover) {
            /**
             * @var FilesystemAdapter $storage
             */
            $storage = Storage::disk(config('filesystems.public'));

            return $storage->url("mcovers/$this->cover");
        }

        return '';
    }
}
