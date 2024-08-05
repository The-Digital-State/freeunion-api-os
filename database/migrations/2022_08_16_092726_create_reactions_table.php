<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reactions', static function (Blueprint $table) {
            $table->id();

            $table->morphs('model');
            $table->foreignIdFor(User::class)->nullable()
                ->constrained()
                ->nullOnDelete();
            $table->unsignedInteger('reaction');

            $table->unique(['model_type', 'model_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reactions');
    }
};
