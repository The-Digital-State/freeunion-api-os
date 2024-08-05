<?php

declare(strict_types=1);

use App\Models\Comment;
use App\Models\DeskComment;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::beginTransaction();

        DeskComment::with('deskTask')->each(static function (DeskComment $deskComment) {
            $thread = $deskComment->deskTask->getThread();

            $comment = new Comment();
            $comment->forceFill([
                'comment_thread_id' => $thread->id,
                'user_id' => $deskComment->user_id,
                'comment' => $deskComment->comment ?? '',
                'created_at' => $deskComment->created_at,
                'updated_at' => $deskComment->updated_at,
            ]);
            $comment->timestamps = false;
            $comment->save();
        });

        DB::commit();
    }
};
