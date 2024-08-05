<?php

declare(strict_types=1);

use App\Models\Comment;
use App\Models\CommentThread;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comments', static function (Blueprint $table) {
            $table->id();

            $table->foreignIdFor(CommentThread::class)
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignIdFor(Comment::class)->nullable()
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignIdFor(User::class)->nullable()
                ->constrained()
                ->cascadeOnDelete();
            $table->text('comment');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
