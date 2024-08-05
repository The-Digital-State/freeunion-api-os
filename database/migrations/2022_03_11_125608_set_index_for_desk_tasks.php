<?php

declare(strict_types=1);

use App\Models\DeskTask;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SetIndexForDeskTasks extends Migration
{
    public function up(): void
    {
        /** @var Collection<int, Collection<int, Collection<int, DeskTask>>> $deskTasks */
        $deskTasks = DeskTask::query()->get()->groupBy(['organization_id', 'column_id']);

        DB::beginTransaction();

        $deskTasks->each(static function (Collection $tasksInOrganization) {
            $tasksInOrganization->each(static function (Collection $tasksInColumn) {
                $counter = 0;
                $tasksInColumn->each(static function (DeskTask $deskTask) use (&$counter) {
                    $deskTask->index = $counter++;
                    $deskTask->save();
                });
            });
        });

        DB::commit();
    }

    public function down(): void
    {
        // Nothing to do
    }
}
