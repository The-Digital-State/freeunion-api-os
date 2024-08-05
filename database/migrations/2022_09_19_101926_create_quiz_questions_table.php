<?php

declare(strict_types=1);

use App\Models\Quiz;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_questions', static function (Blueprint $table) {
            $table->id();

            $table->foreignIdFor(Quiz::class)
                ->constrained()
                ->cascadeOnDelete();
            $table->string('question');
            $table->longText('settings');
            $table->unsignedInteger('index');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_questions');
    }
};
