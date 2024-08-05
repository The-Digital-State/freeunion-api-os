<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * Class DocTemplate
 *
 * @property int $id
 * @property string|null $name
 * @property int|null $organization_id
 * @property string $template
 * @property array $fields
 * @property array $previews
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property Organization $organization
 */
class DocTemplate extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'organization_id', 'template', 'fields', 'previews',
    ];

    protected $casts = [
        'fields' => 'array',
        'previews' => 'array',
    ];

    public function getPreviews(): array
    {
        if ($this->previews) {
            $result = [];

            /**
             * @var FilesystemAdapter $storage
             */
            $storage = Storage::disk(config('filesystems.public'));

            foreach ($this->previews as $preview) {
                $result[] = $storage->url("doc_templates/$preview");
            }

            return $result;
        }

        return [];
    }

    /**
     * @return BelongsTo<Organization, DocTemplate>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
