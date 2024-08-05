<?php

declare(strict_types=1);

namespace App\Models\Traits;

use App\Models\CommentThread;
use Illuminate\Database\Eloquent\Relations\MorphOne;

trait HasComments
{
    /**
     * @return MorphOne<CommentThread>
     */
    public function thread(): MorphOne
    {
        return $this->morphOne(CommentThread::class, 'model');
    }

    /**
     * @return CommentThread
     */
    public function getThread(): CommentThread
    {
        /** @var CommentThread $thread */
        $thread = $this->thread()->firstOrCreate();

        if (! $thread->exists) {
            $thread->save();
        }

        return $thread;
    }
}
