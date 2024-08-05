<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Storage;

class MoveBanners extends Migration
{
    public function up(): void
    {
        $storage = Storage::disk(config('filesystems.public'));
        $storage->move('banners', 'bims');
    }

    public function down(): void
    {
        $storage = Storage::disk(config('filesystems.public'));
        $storage->move('bims', 'banners');
    }
}
