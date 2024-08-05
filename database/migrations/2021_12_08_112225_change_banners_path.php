<?php

declare(strict_types=1);

use App\Models\Banner;
use Illuminate\Database\Migrations\Migration;

class ChangeBannersPath extends Migration
{
    public function up(): void
    {
        $storage = Storage::disk(config('filesystems.public'));

        Banner::all()->each(static function (Banner $banner) use ($storage) {
            foreach (['large', 'small'] as $size) {
                if ($storage->exists("banners/{$banner->$size}")) {
                    if ($storage->exists("bims/{$banner->$size}")) {
                        $storage->delete("banners/{$banner->$size}");
                    } else {
                        $storage->move("banners/{$banner->$size}", "bims/{$banner->$size}");
                    }
                }
            }
        });

        $storage->delete('banners');
    }

    public function down(): void
    {
        $storage = Storage::disk(config('filesystems.public'));

        Banner::all()->each(static function (Banner $banner) use ($storage) {
            foreach (['large', 'small'] as $size) {
                if ($storage->exists("bims/{$banner->$size}")) {
                    if ($storage->exists("banners/{$banner->$size}")) {
                        $storage->delete("bims/{$banner->$size}");
                    } else {
                        $storage->move("bims/{$banner->$size}", "banners/{$banner->$size}");
                    }
                }
            }
        });

        $storage->delete('banners');
    }
}
