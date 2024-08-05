<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\DeskTask;
use App\Models\Material;
use App\Models\News;
use App\Models\Organization;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;

class OganizationReorderCommand extends Command
{
    protected $signature = 'organization:reorder';

    protected $description = 'Command description';

    public function handle(): void
    {
        $indicators = new Collection();

        $dateFrom = Date::now()->subYear()->subDays(30)->startOfDay();
        $sort = Organization::count();

        Organization::query()
            ->withCount([
                'members',
                'suggestions' => fn (Builder $query) => $query
                    ->whereDate('created_at', '>', $dateFrom),
                'deskTasks' => fn (Builder $query) => $query
                    ->where('visibility', DeskTask::VISIBILITY_ALL)
                    ->whereDate('created_at', '>', $dateFrom),
                'news' => fn (Builder $query) => $query
                    ->where('visible', News::VISIBLE_ALL)
                    ->whereNotNull('published_at')
                    ->whereDate('created_at', '>', $dateFrom),
                'materials' => fn (Builder $query) => $query
                    ->whereIn('visible', [Material::VISIBLE_ALL, Material::VISIBLE_USERS])
                    ->whereNotNull('published_at')
                    ->whereDate('created_at', '>', $dateFrom),
            ])
            ->each(static function (Organization $organization) use (&$indicators) {
                $indicators->put($organization->id, [
                    'id' => $organization->id,
                    'members' => round($organization->members_count / 10, 0, PHP_ROUND_HALF_DOWN),
                    'tasks' => round(($organization->suggestions_count + $organization->desk_tasks_count) / 5, 0,
                        PHP_ROUND_HALF_DOWN),
                    'news' => $organization->news_count + $organization->materials_count,
                ]);
            });

        DB::beginTransaction();

        $indicators->sortBy([
            ['news', 'desc'],
            ['tasks', 'desc'],
            ['members', 'desc'],
            ['id', 'desc'],
        ])->each(static function (array $item) use (&$sort) {
            $organization = Organization::find($item['id']);

            if ($organization instanceof Organization) {
                $organization->forceFill([
                    'sort' => ($item['news'] + $item['tasks'] + $item['members']) ? $sort-- : 0,
                ])->save();
            }
        });

        DB::commit();
    }
}
