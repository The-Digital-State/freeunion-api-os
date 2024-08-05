<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Quiz;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class QuizActivateCommand extends Command
{
    protected $signature = 'quiz:activate';

    protected $description = 'Command description';

    public function handle(): void
    {
        $quizzes = Quiz::query()
            ->where('published', true)
            ->where('is_active', false)
            ->where(static function (Builder $query) {
                $query->whereNull('date_start');
                $query->orWhereDate('date_start', '<=', now());
            })
            ->get();

        if ($quizzes->count()) {
            DB::beginTransaction();

            $quizzes->each(static function (Quiz $quiz) {
                $quiz->is_active = true;
                $quiz->save();
            });

            DB::commit();
        }
    }
}
