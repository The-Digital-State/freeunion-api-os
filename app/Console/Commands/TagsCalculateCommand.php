<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\NewsTag;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TagsCalculateCommand extends Command
{
    protected $signature = 'tags:calculate';

    protected $description = 'Command description';

    public function handle(): void
    {
        $result = NewsTag::query()->withCount(['news', 'materials'])
            ->withMax('news', 'published_at')
            ->withMax('materials', 'published_at')
            ->get();

        DB::beginTransaction();

        foreach ($result as $item) {
            $item->count = $item->materials_count + $item->news_count;
            $item->last_published_at = max($item->materials_max_published_at, $item->news_max_published_at);
            $item->save();
        }

        DB::commit();
    }
}
